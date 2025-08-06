<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
         // On trouve l'utilisateur demandé. Si l'ID n'existe pas,
        // ModelNotFoundException est levée et gérée par le Handler.php.
        $requestedUser = User::findOrFail($id);
        
        // On récupère l'utilisateur authentifié. On suppose qu'il existe,
        // car si ce n'était pas le cas, le middleware aurait déjà levé une erreur.
        $authenticatedUser = Auth::user();

        // C'est ici que se trouve votre test d'autorisation :
        if ($authenticatedUser->id != $requestedUser->id) {
            // Si les IDs ne correspondent pas, l'accès est interdit.
            return response()->json([
                'error' => 'You are not authorized to view this user.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Si tout est bon, on retourne les données.
        $userData = $requestedUser->toArray();
        $userData['message'] = 'Data retrived with success';
        
        return response()->json($userData, Response::HTTP_OK);
    
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
