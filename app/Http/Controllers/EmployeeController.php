<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use App\Models\Activity;
use App\Models\Photo;
use App\Models\EmployeeDailyRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class EmployeeController extends Controller
{
    private function getValidationRules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'department' => 'required|string',
            'phone_number' => 'required|string|regex:/^[0-9]{10}$/',
            'address' => 'required|string|max:255',
            'shift_starts' => 'required|date_format:H:i',
            'shift_ends' => 'required|date_format:H:i',
            'info_description' => 'nullable|string',
            'salary' => 'required|numeric',
            'position' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'total_score' => 'nullable|integer',
        ];
    }

    private function formatEmployeeData(Employee $employee)
    {
        $photos = $employee->photos->pluck('photo_path')->toArray();
        $profilePhoto = $employee->user->profile_photo;

        if ($profilePhoto) {
            $photos = array_merge([$profilePhoto], array_diff($photos, [$profilePhoto]));
        }

        return [
            'id' => $employee->id,
            'user_id' => $employee->user_id,
            'first_name' => $employee->user->first_name,
            'last_name' => $employee->user->last_name,
            'department' => $employee->department,
            'phone_number' => $employee->phone_number,
            'address' => $employee->address,
            'shift_starts' => $employee->shift_starts,
            'shift_ends' => $employee->shift_ends,
            'info_description' => $employee->info_description,
            'total_score' => $employee->total_score,
            'birth_date' => $employee->birth_date,
            'photos' => $photos,
        ];
    }

    public function listAllEmployees()
    {
        $employees = Employee::with(['photos', 'user'])->get();
        $formattedEmployees = $employees->map(fn(Employee $employee) => $this->formatEmployeeData($employee));

        return response()->json([
            'status' => 'success',
            'data' => $formattedEmployees,
        ], 200);
    }

    public function getEmployeeDetails(Employee $employee)
    {
        $employee->load(['photos', 'user']);
        return response()->json([
            'status' => 'success',
            'data' => $this->formatEmployeeData($employee),
        ], 200);
    }

    public function createEmployee(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            $user = User::create([
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'email' => $validatedData['email'],
                'password' => bcrypt($validatedData['password']),
                'profile_photo' => $this->storeProfilePhoto($request, $validatedData['first_name']),
            ]);

            $employee = Employee::create(
                array_merge(
                    ['user_id' => $user->id],
                    $validatedData
                )
            );

            $this->storeEmployeePhotos($request, $employee);
            $this->postPhotosToExternalApi($employee, $request->file('photos'));
            return response()->json([
                'status' => 'success',
                'data' => $this->formatEmployeeData($employee),
                'message' => 'Employee created successfully.',
            ], 201);

        } catch (\Exception $e) {
            return $this->handleException($e, 'Error creating employee');
        }
    }

    public function updateEmployee(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $employee->update($validatedData);

            if ($request->hasFile('photos')) {
                $this->updateEmployeePhotos($request, $employee);
            }

            return response()->json([
                'status' => 'success',
                'data' => $this->formatEmployeeData($employee),
                'message' => 'Employee updated successfully.',
            ], 200);

        } catch (\Exception $e) {
            return $this->handleException($e, 'Error updating employee');
        }
    }
    public function deleteEmployee(Employee $employee)
    {
        try {
            $this->deleteEmployeePhotos($employee);

            $user = $employee->user;
            if ($user) {
                $user->delete();
            }

            $employee->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Employee and associated user deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Error deleting employee');
        }
    }

    public function calculateEmployeeDailyPoints(Request $request, $employee_id)
    {
        $validator = Validator::make($request->all(), ['date' => 'required|date']);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = $validator->validated()['date'];
        $totalPoints = EmployeeDailyRecord::where('employee_id', $employee_id)
            ->whereDate('record_date', $date)
            ->get()
            ->sum(fn($record) => $record->score);

        return response()->json([
            'status' => 'success',
            'data' => ['score' => $totalPoints],
        ], 200);
    }
    public function calculateEmployeeMonthlyPoints(Request $request, $employee_id)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $month = $validator->validated()['month'];
        $year = $validator->validated()['year'];

        $totalPoints = EmployeeDailyRecord::where('employee_id', $employee_id)
            ->whereYear('record_date', $year)
            ->whereMonth('record_date', $month)
            ->get()
            ->sum(fn($record) => $record->score);

        return response()->json([
            'status' => 'success',
            'data' => ['score' => $totalPoints],
        ], 200);
    }

    public function calculateAllEmployeesMonthlyPoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $month = $validator->validated()['month'];
        $year = $validator->validated()['year'];

        Employee::all()->each(function (Employee $employee) use ($month, $year) {
            $totalPoints = EmployeeDailyRecord::where('employee_id', $employee->id)
                ->whereYear('record_date', $year)
                ->whereMonth('record_date', $month)
                ->get()
                ->sum(fn($record) => $record->score);

            $employee->update(['total_score' => $totalPoints]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Monthly points calculated for all employees',
        ], 200);
    }


    public function calculateEmployeeTotalPoints(Request $request, $employee_id)
    {
        $employee = Employee::findOrFail($employee_id);
        $totalPoints = EmployeeDailyRecord::where('employee_id', $employee->id)
            ->get()
            ->sum(fn($record) => $record->score);

        $employee->update(['total_score' => $totalPoints]);

        return response()->json([
            'status' => 'success',
            'data' => ['score' => $totalPoints],
        ], 200);
    }

    public function calculateAllEmployeesTotalPoints()
    {
        Employee::all()->each(function (Employee $employee) {
            $totalPoints = EmployeeDailyRecord::where('employee_id', $employee->id)
                ->get()
                ->sum(fn($record) => $record->score);

            $employee->update(['total_score' => $totalPoints]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Total points calculated for all employees',
        ], 200);
    }

    private function storeProfilePhoto(Request $request, string $s)
    {
        if ($request->hasFile('photos')) {
            $filename = time() . '_' . '0' . '.' . $request->file('photos')[0]->getClientOriginalExtension();
            $path = $request->file('photos')[0]->storeAs('public/profile_photos', $filename);
            return $path;
        }

        return null;
    }

    private function storeEmployeePhotos(Request $request, Employee $employee)
    {

        $photos = $request->file('photos') ?? [];
        $photoPaths = [];

        if (count($photos) > 0) {
            foreach (array_slice($photos, 1) as $index => $photo) {
                $filename = time() . '_' . $index . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs('public/' . $employee->user->first_name, $filename);
                Photo::create([
                    'employee_id' => $employee->id,
                    'photo_path' => $path,
                ]);
                $photoPaths[] = $photo;
            }

            // $this->postPhotosToExternalApi($employee, $photoPaths);
        }
    }

    private function updateEmployeePhotos(Request $request, Employee $employee)
    {
        $photos = $request->file('photos') ?? [];
        $photoPaths = [];

        if (count($photos) > 0) {
            $profilePhotoPath = $photos[0]->store('profile_photos', 'public');
            $employee->user->update(['profile_photo' => $profilePhotoPath]);

            foreach (array_slice($photos, 1) as $photo) {
                $filename = time() . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs('public/' . $employee->nickname, $filename);
                Photo::updateOrCreate(
                    ['employee_id' => $employee->id, 'photo_path' => $path],
                    ['photo_path' => $path]
                );
                $photoPaths[] = $photo;
            }

            // $this->postPhotosToExternalApi($employee, $photoPaths);
        }
    }

    private function deleteEmployeePhotos(Employee $employee)
    {
        foreach ($employee->photos as $photo) {
            Storage::delete('public/' . $photo->photo_path);
        }

        if ($employee->user->profile_photo) {
            Storage::delete('public/' . $employee->user->profile_photo);
        }
    }

    private function postPhotosToExternalApi(Employee $employee, array $photos)
    {

        $URL = 'http://192.168.43.172:5000';
        $client = Http::asMultipart();

        $client = $client->attach('id', $employee->id)
            ->attach('first_name', $employee->user->first_name)
            ->attach('last_name', $employee->user->last_name);

        foreach ($photos as $photo) {
            $client = $client->attach(
                'photos',
                file_get_contents($photo->getPathname()),
                $photo->getClientOriginalName()
            );
        }

        $response = $client->post($URL.'/upload');

        // if ($response->failed()) {
        //     echo 'Failed to post photos to external API';
        //     echo 'Status: ' . $response->status();
        //     echo 'Response: ' . $response->body();
        // } else {
        //     echo 'Successfully posted photos to external API';
        //     echo 'Response: ' . json_encode($response->json());
        // }

    }

    private function handleException(\Exception $e, $message)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message . ': ' . $e->getMessage(),
        ], 500);
    }
}
