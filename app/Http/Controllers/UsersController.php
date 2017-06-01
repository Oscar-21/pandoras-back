<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Subscribe;
use Response;
use Purifier;
use Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Auth;

class UsersController extends Controller
{

  public function __construct()
  {
    $this->middleware('jwt.auth', ['only'=>['deleteUser','show','index','updateAddress', 'adminShowUser','userShow']]);
  }
  
  /*
   *  ADMIN: Show all user information
   */ 
  public function index()
  {
    $user = user::where('id', 1)->first();

    if ($user->subscribedToPlan('monthly', 'main')) 
    {
      return Response::json(['success' => 'user is subscribed']);
    }
    return Response(['hell' => 'naaq']);
    return Response::json($user);
  }
  public function signUp(Request $request)
  {
    // Required form fields
    $validator = Validator::make(Purifier::clean($request->all()), [
      'username' => 'required',
      'password' => 'required',
      'address' => 'required',
      'zipCode' => 'required',
      'email' => 'required',
      'city' => 'required',
      'country' => 'required',
    ]);

    if ($validator->fails())
    {
      return Response::json(['error' => 'You must fill out all fields.']);
    }

    $check = User::where('email',$request->input('email'))->orWhere('name',$request->input('username'))->first();

    if (!empty($check))
    {
      return Response::json(['error' => 'Email or username alrready in use.']);
    }

    $user = new User;

    $user->name = $request->input('username');
    $user->password = Hash::make($request->input('password'));
    $user->email = $request->input('email');
    $user->address = $request->input('address');
    $user->zipCode = $request->input('zipCode');
    $user->city= $request->input('city');
    $user->country= $request->input('country');
    $user->roleID = 2;

    /*
    * laravel/cashier methods to populate a users table 
    * with Stripe data...create subscription... update subscription 
    * table
    */
    $user->newSubscription('main', 'monthly')->create($cardToken);

    $user->save();

    return Response::json(['success' => 'User created successfully']);
  }

  public function show()
  {

  }
  public function signIn(Request $request)
  {
    $validator = Validator::make(Purifier::clean($request->all()), [
      'email' => 'required',
      'password' => 'required'
    ]);

    if ($validator->fails()) 
    {
      return Response::json(["error" => "You must enter an email and a password."]);
    }
      $email = $request->input('email');
      $password = $request->input('password');
      $cred = compact("email","password",["email","password"]);
      $token = JWTAuth::attempt($cred);
      return Response::json(compact("token"));
  }

  
  public function isUserSubscribed($id)
  {
    $user = User::where('id', $id)->first();
    if ($user->subscribedToPlan('monthly', 'main')) 
    {
      return Response::json(true);
    }
    return Response::json(false);
  }
}
