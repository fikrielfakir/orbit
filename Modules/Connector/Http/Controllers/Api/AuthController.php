
<?php

namespace Modules\Connector\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\User;
use Illuminate\Support\Facades\Validator;

class AuthController extends ApiController
{
    /**
     * Login user
     *
     * @bodyParam username string required The username or email
     * @bodyParam password string required The password
     * 
     * @response {
     *   "success": true,
     *   "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
     *   "token_type": "Bearer",
     *   "expires_in": 31536000,
     *   "user": {
     *     "id": 1,
     *     "username": "admin",
     *     "email": "admin@example.com",
     *     "first_name": "Admin",
     *     "last_name": "User"
     *   }
     * }
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'validation_error',
                'message' => 'Invalid input data',
                'errors' => $validator->errors()
            ], 422);
        }

        $username = $request->input('username');
        $password = $request->input('password');

        // Try to find user by username or email
        $user = User::where('username', $username)
                   ->orWhere('email', $username)
                   ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_credentials',
                'message' => 'Invalid username or password'
            ], 401);
        }

        if ($user->allow_login == 0) {
            return response()->json([
                'success' => false,
                'error' => 'account_disabled',
                'message' => 'Your account has been disabled'
            ], 403);
        }

        // Create token
        $token = $user->createToken('API Token')->accessToken;

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 31536000, // 1 year
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'business_id' => $user->business_id
            ]
        ]);
    }

    /**
     * Get logged in user details
     *
     * @authenticated
     * @response {
     *   "id": 1,
     *   "username": "admin",
     *   "email": "admin@example.com",
     *   "first_name": "Admin",
     *   "last_name": "User",
     *   "business_id": 1
     * }
     */
    public function getLoggedInUser(Request $request)
    {
        $user = Auth::user();
        
        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'business_id' => $user->business_id,
            'contact_no' => $user->contact_no,
            'language' => $user->language,
            'status' => $user->status
        ]);
    }

    /**
     * Logout user
     *
     * @authenticated
     * @response {
     *   "success": true,
     *   "message": "Successfully logged out"
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        
        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }
}
