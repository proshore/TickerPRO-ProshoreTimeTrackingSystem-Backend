<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\MemberInviteRequest;
use App\Services\InviteService;
use App\Services\UserService;
use App\Models\User;
use Mockery\Exception;
use App\Http\Resources\AdminResource;
use App\Http\Resources\RoleResource;
use App\Traits\HttpResponses;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Doctrine\DBAL\Query\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


class AdminController extends Controller
{   
    use HttpResponses;
    /**
     * @var mixed
     *
     * @var UserService
     */
    protected UserService $userService;
    protected User $userModel;
    
    public function __construct(UserService $userService, User $userModel)
    {
        $this->userService = $userService;
        $this->userModel = $userModel;

    }


    public function deleteUser($id)
    {
        $user = User::where('id', $id)->first();

        $deleteStatus = UserService::checkUserIdExists($id);

        if (!$deleteStatus) {
            return response()->json([
                'message' => 'User does not exist with given id'
            ], Response::HTTP_NOT_FOUND);
        }

        $roles = $user->roles()->pluck('role');

        if ($roles->contains('admin')) {

            return response()->json([
                'message' => 'Admin User cannot be deleted'
            ], 403);
        }

        if ($user->delete()) {

            return response()->json([
                'message' => 'User deleted Successfully'
            ], 200);
        }

        return response()->json([
            'message' => 'Oops Something Went Wrong'
        ], 403);
    }

    public function viewAllUsers()
    {
        $users = User::latest()->get();

        return response()->json([
            'total' => count($users),
            'users' => AdminResource::collection($users)
        ], 200);
    }


    /**
     *
     * @param Request $request
     * @throws ModelNotFoundException
     * @throws Exception
     * @return JsonResponse
     */
    public function viewUserRole(Request $request): JsonResponse
    {
        try {
            $role = User::findOrFail(Arr::get($request, 'id'))->roles;
            $data = [
                'total' => count($role),
                'roles' => RoleResource::collection($role)
            ];
            return $this->successResponse($data, "User Role Successfully Retrieved");
        } catch (ModelNotFoundException $modelNotFoundException) {
            Log::error($modelNotFoundException->getMessage());
            return $this->errorResponse([], "User does not exists", Response::HTTP_NOT_FOUND);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return $this->errorResponse([], "Something went wrong");
        }
    }



    /**
     * @param AssignRoleRequest $request
     * @return JsonResponse
     * @throws Exception
     * @throws ModelNotFoundException
     * @throws QueryException
     */

    public function assignRoles(AssignRoleRequest $request): JsonResponse
    {
        try {
            $roles = $this->userService->assignUserRole($request->validated());
            return $this->successResponse([RoleResource::collection($roles)], 'User is assigned the role ', Response::HTTP_OK);
        } catch (ModelNotFoundException $modelNotFoundException) {
            Log::error($modelNotFoundException->getMessage());
            return $this->errorResponse([], "User not Found", Response::HTTP_NOT_FOUND);
        } catch (QueryException $queryException) {
            Log::error($queryException->getMessage());
            return $this->errorResponse([], 'Cannot assign role to User', Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return $this->errorResponse([], 'Something went wrong');
        }
    }


    public function inviteOthers(MemberInviteRequest $request, InviteService $inviteService)
    {
        $validated = $request->safe()->only(['role_id', 'email', 'user_id', 'name']);
        $status = $inviteService->invite($validated['name'], $validated['email'], $validated['role_id'], $validated['user_id']);

        if (!$status) {
            return response()->json([
                'message' => 'User could not be invited'
            ], 500);
        }

        return response()->json([
            'message' => 'User invited successfully'
        ], 200);
    }


}