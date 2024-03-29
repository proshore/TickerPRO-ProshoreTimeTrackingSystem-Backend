<?php

namespace App\Http\Controllers\Actions\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimelogReportRequest;
use App\Services\{ReportService, UserService};
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class GenerateReportAction extends Controller
{
    protected ReportService $reportService;
    protected UserService $userService;

    /**
     * @param ReportService $reportService
     */
    public function __construct(ReportService $reportService, UserService $userService)
    {
        $this->reportService = $reportService;
        $this->userService = $userService;
    }

    /**
     *
     * @param TimelogReportRequest $request
     * @return JsonResponse
     */
    public function __invoke(TimelogReportRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $user = auth()->user();
            if (!$this->userService->hasRoleAdmin($user)) {
                $validated['user_ids'] = [auth()->id()];
            }
            $report = $this->reportService->getUsersReport($validated);
            return $this->successResponse([$report], 'Report successfully retrieved');
        } catch (ModelNotFoundException $modelNotFoundException) {
            Log::error($modelNotFoundException->getMessage());
            return $this->errorResponse([], "User does not exist.", Response::HTTP_NOT_FOUND);
        } catch (QueryException $queryException) {
            Log::error($queryException->getMessage());
            return $this->errorResponse([], "Could not generate Report", Response::HTTP_NOT_FOUND);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return $this->errorResponse([], "Something went wrong.");
        }
    }
}
