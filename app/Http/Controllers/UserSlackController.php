<?php

namespace App\Http\Controllers;

use App\Models\UserSlack;
use Illuminate\Http\Request;

class UserSlackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return UserSlack::all();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): array
    {
        $userSlack = new UserSlack();
        $operation = 'save';
        $existingUser = UserSlack::find($request->get('user_id'));
        if ($existingUser) {
            $userSlack = $existingUser;
            $operation = 'update';
        }

        $userSlack->user_id = $request->get('user_id');
        $userSlack->team_id = $request->get('team_id');
        $userSlack->user_token = $request->get('user_token');
        $userSlack->bot_token = $request->get('bot_token');

        $userSlack->$operation();

        return $userSlack->toArray();
    }

    /**
     * Display the specified resource.
     */
    public function show(UserSlack $userSlack)
    {
        return $userSlack;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserSlack $userSlack)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserSlack $userSlack)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserSlack $userSlack)
    {
        //
    }
}
