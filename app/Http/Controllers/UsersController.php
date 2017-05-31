<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Response;
use Purifier;
use Hash;
use Subscrpition;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{

  public function signUp(Request $request) 
  {
    $stripeToken = config('services.stripe.key');
/*    $stripeSecret = config('services.stripe.secret'); */

    $validator = Validator::make(Purifier::clean($request->all()), [
      'username' => 'required',
      'password' => 'required',
      'address' => 'required',
      'zipCode' => 'required',
      'email' => 'required',
    ]);

    if ($validator->fails()) 
    {
      return Response::json(["error" => "You must eneter a username, email and password."]);
    }

    $check = User::where("email","=",$request->input("email"))->orWhere("name","=",$request->input("username"))->first();

    if (!empty($check)) 
    {
      return Response::json(["error" => "Email or username alrready in use."]);
    }

    $user = new User;
    $user->name = $request->input('username');
    $user->password = Hash::make($request->input('password'));
    $user->email = $request->input('email');
    $user->address = $request->input('address');
    $user->zipCode = $request->input('zipCode');
    $user->roleID = 2;
  /*   return Response(['hell' => 'naa']); */

/*    \Stripe\Stripe::setApiKey($stripeSecret); 

/*    \Stripe\Customer::create(array(
      "description" => "Customer for jayden.wilson@example.com",
      "source" => "tok_189gGV2eZvKYlo2Cy0Ke9b1G" // obtained with Stripe.js
    )); */

/*    $shopper = \Stripe\Customer::retrieve("cus_AktlHXJJIdOYPZ");

    $customerToken = "cus_AktlHXJJIdOYPZ"; 

    /*$plan = \Stripe\Plan::create(array(
      "name" => "Basic",
      "id" => "basic-monthly",
      "interval" => "month",
      "currency" => "usd",
      "amount" => 0,
    ));*/

    $user->newSubscription('monthly', 'monthly')->create($stripeToken);
/*    return Response::json($user.stripe_id); */
    $user->save();
    return Response::json($user.stripe_id);
    return Response::json(["success" => "User created successfully"]);
  }

  public function try() 
  {
    $stripeToken = config('services.stripe.key');
    return $stripeToken;
  }

}
