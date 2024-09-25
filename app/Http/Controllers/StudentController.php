<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    // Path to the JSON file where students will be stored
    protected $filePath = 'students.json';

    /**
     * Helper function to retrieve students from the JSON file.
     *
     * @return array
     */
    protected function getStudents()
    {
        if (!Storage::exists($this->filePath)) {
            // Initialize the file with an empty array if it doesn't exist
            Storage::put($this->filePath, json_encode([]));
        }

        $json = Storage::get($this->filePath);
        return json_decode($json, true);
    }

    /**
     * Helper function to save students to the JSON file.
     *
     * @param array $students
     * @return void
     */
    protected function saveStudents($students)
    {
        Storage::put($this->filePath, json_encode($students, JSON_PRETTY_PRINT));
    }

    /**
     * List all students.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $students = $this->getStudents();

        if (empty($students)) {
            return response()->json(['message' => 'No students found'], 200);
        }

        return response()->json($students, 200);
    }

    /**
     * Store a new student.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate incoming request data
        $validated = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName'  => 'required|string|max:255',
            'course'    => 'required|string',
            'year'      => ['required', Rule::in(['First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Fifth Year'])],
            'enrolled'  => 'required|boolean',
        ]);

        $students = $this->getStudents();

        // Assign a new unique ID
        $newId = empty($students) ? 1 : max(array_column($students, 'id')) + 1;

        $newStudent = array_merge(['id' => $newId], $validated);
        $students[] = $newStudent;

        $this->saveStudents($students);

        return response()->json($newStudent, 201);
    }

    /**
     * Display a specific student by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $students = $this->getStudents();

        $student = collect($students)->firstWhere('id', (int)$id);

        return $student
            ? response()->json($student, 200)
            : response()->json(['message' => 'Student not found'], 404);
    }

    /**
     * Update a specific student by ID.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $students = $this->getStudents();

        // Find the index of the student with the given ID
        $studentIndex = collect($students)->search(fn($student) => $student['id'] == $id);

        if ($studentIndex === false) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Validate incoming request data
        $validated = $request->validate([
            'firstName' => 'sometimes|required|string|max:255',
            'lastName'  => 'sometimes|required|string|max:255',
            'course'    => 'sometimes|required|string',
            'year'      => ['sometimes', 'required', Rule::in(['First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Fifth Year'])],
            'enrolled'  => 'sometimes|required|boolean',
        ]);

        // Update the student details
        $students[$studentIndex] = array_merge($students[$studentIndex], $validated);
        $this->saveStudents($students);

        return response()->json($students[$studentIndex], 200);
    }

    /**
     * Delete a specific student by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $students = $this->getStudents();

        // Find the index of the student with the given ID
        $studentIndex = collect($students)->search(fn($student) => $student['id'] == $id);

        if ($studentIndex === false) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Remove the student from the array
        array_splice($students, $studentIndex, 1);
        $this->saveStudents($students);

        return response()->json(['message' => 'Student deleted successfully'], 200);
    }
}
