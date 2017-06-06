<?php
namespace App\Http\Controllers;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use App\User;
use App\Subscription;
use App\Role;
use Response;
use Purifier;
use Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Auth;
use Carbon\Carbon;
use Dompdf\Dompdf;

class UsersController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth', ['only'=>[
      'deleteUser',
      'show',
      'indexUser',
      'indexAdmin',
      'updateAddress', 
      'adminShowUser',
      'userShow',
      'isUserSubscribed',
      'newAdmin'
    ]]);
  }
  
  /*
   *  ADMIN: Show all user information
   */ 
  public function indexUsers()
  {
      $admin = Auth::user();

      if ($admin->roleID != 'Admin' )
      {
        return Response::json(['error' => 'invalid credentials']);
      }
        
      $users = User::join('subscriptions','users.id','subscriptions.user_id')
        ->select('users.*', 'subscriptions.*')->get();

      return Response::json($users);
  }

  /*
   *  User: Check Subscritption Status
   */ 
  public function indexAdmin()
  {
      $admin = Auth::user();

      if ($admin->roleID != 'Admin' )
      {
        return Response::json(['error' => 'invalid credentials']);
      }

      $allAdmins = User::where('roleID', '==', 'Admin')->select(
        'name',
        'email', 
        'roleID' 
      )->get();
    
    return Response::json($allAdmins);
  }

  public function signUp(Request $request)
  {
    // Required form fields
    $validator = Validator::make(Purifier::clean($request->all()), [
      'username' => 'required',
      'password' => 'required',
      'billingAddress' => 'required',
      'billingZipCode' => 'required',
      'email' => 'required',
      'billingCity' => 'required',
      'billingCountry' => 'required',
      'useBillingAddress' => 'required',
      'plan' => 'required',
    ]);

    if ($validator->fails())
    {
      return Response::json(['error' => 'You must fill out all fields.']);
    }

    // Check to see if email is already in use.
    $check = User::where('email',$request->input('email'))->first();
    if (!empty($check))
    {
      return Response::json(['error' => 'Email or username alrready in use.']);
    }

    $user = new User;

    $user->name = $request->input('username');
    $user->password = Hash::make($request->input('password'));
    $user->email = $request->input('email');
    $user->billingAddress = $request->input('billingAddress');
    $user->billingZipCode = $request->input('billingZipCode');
    $user->billingCity= $request->input('billingCity');
    $user->billingCountry= $request->input('billingCountry');

    // check to see if billiing address is mailing address
    $check = $request['useBillingAddress'];

    if ($check == false) 
    {
      $user->address = $request->input('deliverAddress');
      $user->zipCode = $request->input('deliverZipCode');
      $user->city= $request->input('deliverCity');
      $user->country= $request->input('deliverCountry');
    }
    /*
    * laravel/cashier methods to populate a users table 
    * with Stripe data...create subscription... update subscription 
    * table
    */
    $check = $request['plan'];
    $cardToken = 'tok_1APzw3DRWneWp7Hp7qmRYm8T';
    switch($check) 
    {
      // Tier 1: $29.99
      case 'tierOne':
        $role = Role::where('id',2)->select('name')->first();
        $user->roleID = $role['name'];
        $user->newSubscription('main', 'monthly')->create($cardToken);
        break;

      // Tier 2: $59.99
      case 'tierTwo':
        $role = Role::where('id',3)->select('name')->first();
        $user->roleID = $role['name'];
        $user->newSubscription('tierTwo', 'tierTwo')->create($cardToken);
        break;

      // Tier 3: $79.99
      case 'tierThree':
        $role = Role::where('id',4)->select('name')->first();
        $user->roleID = $role['name'];
        $user->newSubscription('tierThree', 'tierThree')->create($cardToken);
        break;
    }
      $user->save();
    return Response::json(['success' => 'User created successfully']);
  }

  public function show()
  {

  }

  /*
   *  USER: Sign in
   */ 
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

  
  /*
   *  ADMIN: Check Subscritption Status
   */ 
  public function isUserSubscribed($id)
  {
    $user = User::where('id', $id)->first();
    $subscription = Subscription::where('user_id',$id)->first();
    $plan = $subscription['stripe_plan'];
    $name = $subscription['name'];

    if ($user->roleID == 'Admin' || $user->roleID == 'unsubscribed')
    {
      return Response::json($user['name'].' is not subscribed to any plan');
    }

    if ($user->subscribedToPlan($plan, $name)) 
    {
      return Response::json($user['name'].' is subscribed to the '.$user['roleID'].' plan');
    }

 }

  public function tryMe($id)
  {
    $user = User::where('id',$id)->first();
    $getUserID =  User::where('id',$id)->select('id')->first();
    $userID = $getUserID['id'];

    $subscription = Subscription::where('user_id', $userID)->first();
    $getStripeID =  Subscription::where('user_id',$userID)->select('stripe_id')->first();
    $stripeID =  $getStripeID['stripe_id'];

    $customerID = $user['stripe_id'];
    $key = config('services.stripe.secret');
    \Stripe\Stripe::setApiKey($key);

    $customer = \Stripe\Customer::retrieve($customerID);
    $invoice = \Stripe\Invoice::retrieve("in_1AQ00iDRWneWp7Hphlzk37s6");

    $invoices = $user->invoices();
    $response = \Stripe\Invoice::all(array('customer' => $customerID));
    $sub = \Stripe\Subscription::retrieve($stripeID);

    $newResponse = array();
    $newResponse['amount due'] = $response['data'][0]['amount_due'];
    $newResponse['date'] = date("F j, Y, g:i a",$response['data'][0]['date']);

    $dompdf = new Dompdf();
    $dompdf->loadHtml('Date: '.$newResponse['date']."<br/>".'Amount due is: '.$newResponse['amount due']);

    // (Optional) Setup the paper size and orientation
    $dompdf->setPaper('A4', 'landscape');

    // Render the HTML as PDF
    $dompdf->render();

    // Output the generated PDF to Browser
    $dompdf->stream();
  }


  public function tryIt()
  {
    $dompdf = new Dompdf();
    $dompdf->loadHtml('hello world');

    // (Optional) Setup the paper size and orientation
    $dompdf->setPaper('A4', 'landscape');

    // Render the HTML as PDF
    $dompdf->render();

    // Output the generated PDF to Browser
    $dompdf->stream();
  }
  /*
   *  ADMIN: delete user
   */ 
  public function deleteUser($id) 
  {
    $admin = Auth::user();

    if ($admin->roleID != 'Admin' )
    {
      return Response::json(['error' => 'invalid credentials']);
    }
    $user = User::where('id',$id)->first();

    if ($user->roleID != 'unsubscribed')
    {
      return Response::json(['error' => "User's subscription must first be cancelled before account is deleted."]);
    }

    $user->delete();
  }

  /*
   *  ADMIN: give admin privalages to user
   */ 
  public function addAdmin($id)
  {
      $admin = Auth::user();

      if ($admin->roleID != 'Admin' )
      {
        return Response::json(['error' => 'invalid credentials']);
      }
      $user = User::where('id',$id)->first();

      $newRole = Role::where('id',1)->select('name')->first();
      $user->roleID = $newRole['name'];
  }

  /*
   *  ADMIN: cancel user subscription immediately
   */ 
  public function cancelSubs($id)
  {
      $admin = Auth::user();

      if ($admin->roleID != 'Admin' )
      {
        return Response::json(['error' => 'invalid credentials']);
      }

      $user = User::where('id',$id)->first();
      $getUserID =  User::where('id',$id)->select('id')->first();
      $userID = $getUserID['id'];

      $subscription = Subscription::where('user_id', $userID)->first();
      $getStripeID =  Subscription::where('user_id',$userID)->select('stripe_id')->first();
      $stripeID =  $getStripeID['stripe_id'];
    
      $key = config('services.stripe.secret');
      \Stripe\Stripe::setApiKey($key);
      $sub = \Stripe\Subscription::retrieve($stripeID);
      $sub->cancel(); 
      
      $user->roleID = 'unsubscribed';
      $user->save();
      
      $subscription->name = 'unsubscribed';
      $subscription->stripe_plan = 'none';
      $subscription->ends_at = Carbon::now();
      $subscription->save();
  }
}
