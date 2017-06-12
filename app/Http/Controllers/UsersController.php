<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
use XmlParser;

class UsersController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth', ['only'=>[
      'deleteUser',
      'show',
      'indexUsers',
      'indexAdmin',
      'updateAddress', 
      'adminShowUser',
      'userShow',
      'isUserSubscribed',
      'newAdmin',
      'invoicePDF',
      'userCancelNow'
    ]]);
  }


  /*
   *  ADMIN: Show all subscribed users
   */ 
  public function indexUsers()
  {
    $admin = Auth::user();

    if ($admin->roleID != 'Admin')
    {
      return Response::json(['error' => 'invalid credentials']);
    }
      
    $users = User::join('subscriptions','users.id','subscriptions.user_id')
      ->select(
        'users.name',
        'users.email',
        'users.roleID',
        'users.billingCountry',
        'users.billingCity',
        'subscriptions.id',
        'subscriptions.name as tier',
        'subscriptions.stripe_plan',
        'subscriptions.quantity',
        'subscriptions.ends_at',
        'subscriptions.created_at',
        'subscriptions.updated_at'
      )->where('roleID','!=','Admin')->get();

    return Response::json($users);
  }

  /*
   *  ADMIN: Show all site admins
   */ 
  public function indexAdmin()
  {
    return Response::json(['hell' => 'naa']);
    if ($admin->roleID != 'Admin' )
    {
      return Response::json(['error' => 'invalid credentials']);
    }

    $allAdmins = User::where('roleID', '==', 'Admin')->select(
      'name',
      'email'
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
      'customerToken' => 'required'
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
    $check = (bool)$request['useBillingAddress'];
    return Response::json($check);

    if ($check == false); 
    {
      return Response::json($check);
      $user->deliverAddress = $request->input('deliverAddress');
      $user->deliverCity= $request->input('deliverCity');
      $user->deliverZipCode = $request->input('deliverZipCode');
      $user->deliverCountry= $request->input('deliverCountry');
    }
    /*
    * laravel/cashier methods to populate a users table 
    * with Stripe data...create subscription... update subscription 
    * table
    */
    $check = $request['plan'];
    $cardToken = $request['customerToken'];

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
    if($user->save());
    {
      return Response::json(['success' => 'User created successfully']);
    }
      Log::error('Error: Account not created');
      return Response::json(['error' => 'Account not created']);
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
    $subscription = Subscription::where('user_id',$id)->first();
    $plan = $subscription['stripe_plan'];
    $name = $subscription['name'];

    if ($user->subscribedToPlan($plan, $name)) 
    {
      return Response::json($user['name'].' is subscribed to the '.$user['roleID'].' plan');
    }
  }
  public function xml(Request $request)
  {
    $xml = $request->input('xml');
    $xml = XmlParser::extract($request->input('xml'));

    $postage = $xml->parse([
      'size' => ['uses' => 'Package.Size'],
      'pounds' => ['uses' => 'Package.Pounds'],
      'rate' => ['uses' => 'Package.Postage.Rate'],
    ]); 
    return Response::json($postage['rate']);
  }

  public function getPostageKey()
  {
    $postal = config('services.postal.username');
    return Response::json($postal);
  }

  public function tryNow()
  {

  }

  /*
   *  USER: generate pdf file of invoice
   */ 
  public function invoicePDF()
  {
    if (Auth::user()->roleID != 'Admin')
    {
      $user = Auth::user();
      $customerID = $user->stripe_id;

      \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
      $response = \Stripe\Invoice::all(array('customer' => $customerID));

      $dompdf = new Dompdf();
      $dompdf->loadHtml(
        '<h1>'.'Pyxis Invoice'.'</h1>'.
        'Customer: '.$user->name.'<br/>'.
        'Date: '.date("F j, Y, g:i a",$response['data'][0]['date']).'<br/>'.
        'Amount due: $'.substr_replace($response['data'][0]['amount_due'],'.',2,0).'<br/>'. 
        'Current Balance: $'.$response['data'][0]['ending_balance'].'.00' 
      );
      // (Optional) Setup the paper size and orientation
      $dompdf->setPaper('A4', 'landscape');
      // Render the HTML as PDF
      $dompdf->render();
      // Output the generated PDF to Browser
      $dompdf->stream();
    }
  }

  /*
   *  ADMIN: delete user
   */ 
  public function deleteUser($id) 
  {
    if (Auth::user()->roleID == 'Admin')
    {
      $user = User::where('id',$id)->first();

      if ($user->roleID != 'unsubscribed')
      {
        return Response::json(['error' => "User's subscription must first be cancelled before account is deleted."]);
      }

      $user->delete();
    }
  }

  /*
   *  ADMIN: give admin privalages to user
   */ 
  public function addAdmin($id)
  {
      $admin = Auth::user();

      if ($admin->roleID == 'Admin' )
      {
        $user = User::where('id',$id)->first();

        $newRole = Role::where('id',1)->select('name')->first();
        $user->roleID = $newRole['name'];

        if ($user->save())
        {
          return Response::json(['success' => 'Admin privalages given to '.$user->email]);          
        }
        return Response::json(['error' => 'Please try again '.$user->email.' not given Admin Privalages.']);          
      }
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

    $subscription = Subscription::where('user_id', $user->id)->first();
  
    \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
    $account = \Stripe\Subscription::retrieve($subscription->stripe_id);
    $account->cancel(); 
    
    $user->roleID = 'unsubscribed';
    if ($user->save())
    {
      $subscription->name = 'unsubscribed';
      $subscription->stripe_plan = 'none';
      $subscription->ends_at = Carbon::now();

      if ($subscription->save())
      {
        Log::notice($user->email.' account deleted by '.$admin->email);
        return Response::json(['success' => 'Account cancelled and database updated' ]);
      }
    }
  }

  /*
   *  USER: cancel user subscription immediately
   */ 
  public function userCancelNow()
  {
    if (Auth::user()->roleID != 'Admin')
    {
      $user = Auth::user();

      $subscription = Subscription::where('user_id', $user->id)->first();

      \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

      $sub = \Stripe\Subscription::retrieve($subscription->stripe_id);
      $sub->cancel(); 

      $user->roleID = 'unsubscribed';

      if ($user->save())
      {
        $subscription->name = 'unsubscribed';
        $subscription->stripe_plan = 'none';
        $subscription->ends_at = Carbon::now();

        if ($subscription->save())
        {
          Log::notice($user->email.'cancelled their account');
          return Response::json(['success' => 'subscription cancelled']);
        }
      }
      return Response::json(['success' => 'subscription cancelled']);
      Log::critical($user->email.' cancelled their account but database was not updated');
    }
  }

  /*
   *  USER: update billing, delivery, or both addresses
   */ 
  public function userUpdateAddress(Request $request)
  {

    $validator = Validator::make(Purifier::clean($request->all()), [
      'updateBilling' => 'required',
      'updateDelivery' => 'required'
    ]);

    if (Auth::user()->roleID != 'Admin')
    {
      $user = Auth::user();
      if ($request->updateBilling == true)
      {
        $user->billingAddress = $request->input('billingAddress');
        $user->billingCountry = $request->input('billingCountry');
        $user->billingCity = $request->input('billingCity');
        $user->billingZipCode = $request->input('billingZipCode');
      }

      if ($request->updateDelivery == true)
      {
        $user->deliverAddress = $request->input('deliverAddress');
        $user->deliverCountry = $request->input('deliverCountry');
        $user->deliverCity = $request->input('deliverCity');
        $user->deliverZipCode = $request->input('deliverZipCode');
      }
      $user->save();
      return Response::json(['success'=> 'Address updated!']);      
    }
  }

/*http://production.shippingapis.com/ShippingApi.dll?API=RateV4&XML=<RateV4Request USERID="085WEBDE4950">
<Package ID="1ST"> 
<Service>PRIORITY</Service> 
<ZipOrigination>44106</ZipOrigination> 
<ZipDestination>20770</ZipDestination> 
<Pounds>1</Pounds> 
<Ounces>8</Ounces> 
<Container>NONRECTANGULAR</Container> 
<Size>LARGE</Size> 
<Width>15</Width> 
<Length>30</Length> 
<Height>15</Height> 
<Girth>55</Girth> 
</Package> 

</RateV4Request>*/ 
}
