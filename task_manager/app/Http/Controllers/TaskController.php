<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
   public function __construct()
   {
    $this->middleware('auth');
   }
   public function index(): View
   {
    $tasks = Task::where('user_id', Auth::id())->get();
    return view('tasks.index', compact('tasks'));
   }
   public function create(): View
   {
    return view('tasks.create');
   }
   public function store(Request $request): RedirectResponse
   {
    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'status' => 'required|in:pending, completed, in progress',
        'due_date' => 'required|date',
    ]);
    Task::create([
        'title' => $request->title,
        'description' => $request->description,
        'status' => $request->status,
        'due_date' => $request->due_date,
        'user_id' => Auth::id(),
    ]);
    return redirect()->route('tasks.index')->with('success', 'Task created successfully');
   }
   public function edit(Task $task): View
   {
    if ($task->user_id !== Auth::id()) {
        abort(403, 'Unauthorized action.');
    }
    return view('tasks.edit', compact('task'));
   }    
   public function update(Request $request, Task $task): RedirectResponse
   {
    if ($task->user_id !== Auth::id()) {
        abort(403, 'Unauthorized action.');
    }

    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'status' => 'required|in:pending,completed,in progress',
        'due_date' => 'required|date|after_or_equal:today',
    ], [
        'due_date.after_or_equal' => 'The due date must be today or a future date.',
    ]);

    try {
        DB::beginTransaction();

        $task->update($validated);

        DB::commit();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task updated successfully');

    } catch (\Exception $e) {
        DB::rollBack();

        return back()
            ->withInput()
            ->with('error', 'Failed to update task. Please try again.');
    }
   }
   public function destroy(Task $task): RedirectResponse
   {
    $task->delete();
    return redirect()->route('tasks.index')->with('success', 'Task deleted successfully');
   }

}
