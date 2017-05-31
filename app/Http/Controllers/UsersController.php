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
    /*return Response(['hell' => 'naa']);*/
try {
  $user->newSubscription('main', 'monthly')->create('nope ');
  // Use Stripe's library to make requests...
} catch(\Stripe\Error\Card $e) {
    // Since it's a decline, \Stripe\Error\Card will be caught
    $body = $e->getJsonBody();
    $err  = $body['error'];

    print('Status is:' . $e->getHttpStatus() . "\n");
    print('Type is:' . $err['type'] . "\n");
    print('Code is:' . $err['code'] . "\n");
    // param is '' in this case
    print('Param is:' . $err['param'] . "\n");
    print('Message is:' . $err['message'] . "\n");
} catch (\Stripe\Error\RateLimit $e) {
    // Too many requests made to the API too quickly
} catch (\Stripe\Error\InvalidRequest $e) {
    // Invalid parameters were supplied to Stripe's API
} catch (\Stripe\Error\Authentication $e) {
    // Authentication with Stripe's API failed
    // (maybe you changed API keys recently)
} catch (\Stripe\Error\ApiConnection $e) {
    // Network communication with Stripe failed

} catch (\Stripe\Error\Base $e) {
    // Display a very generic error to the user, and maybe send
    // yourself an email
} catch (Exception $e) {
    // Something else happened, completely unrelated to Stripe
}
    $user->save();
    return Response::json(["success" => "User created successfully"]);
  }

  public function try() 
  {
    $stripeToken = config('services.stripe.key');
    return $stripeToken;
  }

}
