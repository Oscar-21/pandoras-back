<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Role;
use JWTAuth;
use Auth;
use Illuminate\Support\Facades\Validator;
use Purifier;

class RolesController extends Controller
{
  # JWT Authentication
  public function __construct() 
  {
    $this->middleware("jwt.auth", ["only"=>["store","destroy","index","show","update"]]);
  }

  # store new role
  public function store(Request $request)
  {
    $validator = Validator::make(Purifier::clean($request->all()), [
      'name' => 'required',
    ]);

    if ($validator->fails())
    {
      return Response::json(["error" => "Invalid input"]);
    }

    $admin = Auth::id();
    return Response::json($admin);

    if ($userID == 1)
    {
      $role = new Role;
      $role->name = $request->input('name');
      $role->save();

      return Response::json(["success" => "You did it!"]);
    }
  }


  public function update($id, Request $request)
  {

    $validator = Validator::make(Purifier::clean($request->all()), [
      'name' => 'required',
    ]);

    if ($validator->fails())
    {
      return Response::json(["error" => "Invalid input"]);
    }

    $userID = Auth::id();

    if ($userID == 1)
    {
      $role = Role::find($id);
      $role->name = $request->input('name');
      $role->save();

      return Response::json(["success" => "You did it!"]);
    }
  }

  public function destroy($id)
  {
    $userID = Auth::id();  
    if ($userID == 1)
    {
      $role = Role::find($id);
      $role->delete();
      return Response::json(['success' => 'You did it!']);
    }
  }

  # 
  public function show($id)
  {
    $userID = Auth::id();  
    if ($userID == 1)
    {
      $role = Role::find($id);
      return Response::json($role);
    }
  }

  # get list of all roles
  public function index()
  {
    $userID = Auth::id();  
    if ($userID == 1)
    {
      $role = Role::all();
      return Response::json($role);
    }
  }
}