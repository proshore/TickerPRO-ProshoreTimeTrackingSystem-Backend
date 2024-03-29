<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TimeLog;
use App\Models\UserProject;
use \Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProjectService
{
    protected TimeLog $timeLog;
    protected Project $project;
    protected UserService $userService;

    public function __construct(TimeLog $timeLog, Project $project, UserService $userService)
    {
        $this->timeLog = $timeLog;
        $this->project = $project;
        $this->userService = $userService;
    }

    public static function addProject(array $validatedAddProject): bool
    {
        $log = Project::create($validatedAddProject);

        $id = auth()->user()->id;
        $userproject = new UserProject;
        $userproject->user_id = $id;
        $userproject->project_id = $log->id;
        $userproject->save();

        if (!is_object($log)) return false;
        return true;
    }

    public static function updateProject($validatedEditProject, $id)
    {
        $project = Project::where('id', $id)->firstOrFail();
        $project->update($validatedEditProject);

        return $project;
    }

    public static function checkProjectIdExists($id)
    {
        $user = Project::where('id', $id)->first();

        if (!$user) return false;

        return true;
    }
    public function removeProject(int $id): void
    {
        $project = Project::where('id', $id)->first();
        if (!$project) {
            throw new Exception("Project With this Id doesnt exist",);
        }
        $project->delete();
    }


    /**
     * @param object $user
     * @param [type] $retrieveOption
     * @return object
     */
    public function listProjects(object $user, $retrieveOption): object
    {
        try {
            $isAdmin = $this->userService->hasRoleAdmin($user);
            if (!$isAdmin && $retrieveOption != Project::STATUS_TRUE) {
                $timelogs = $this->timeLog->where('user_id', auth()->id())->with('project')->get();

                $projects = $timelogs->map(function ($timelog) {
                    return $timelog->only('project')['project']->load('client');
                });

                return $projects->unique();
            }

            return $projects = $this->project->orderBy('updated_at', 'desc')->with('client')->get();
        } catch (ModelNotFoundException) {
            throw new ModelNotFoundException();
        } catch (Exception) {
            throw new Exception();
        }
    }
}
