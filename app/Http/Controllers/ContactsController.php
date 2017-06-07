<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Contact;
use JWTAuth;
use Auth;
use Response;
use Purifier;
use Carbon\Carbon;

class ContactsController extends Controller
{

  public function __construct()
  {
    $this->middleware('jwt.auth', ['only'=>[
      'updateMessage'
    ]]);
  }

  // store message
  public function StorMessage(Request $request) {
    $validator = Validator::make(Purifier::clean($request->all()), [
      'name' => 'required',
      'email' => 'required',
      'message' => 'required',
    ]);
    if ($validator->fails())
    {
      return Reponse::json(['error' => 'You must complete all fields.']);
    }

    $message = new Contact;
    $message->email = $request->input('email');
    $message->name =  $request->input('name');
    $message->message = $request->input('message');

  }

  public function message() {
    $message = new Contact;
    $message->email = 'ted@mail.com';
    $message->name = 'ted boo';
    $message->message = 'this is a message';

    if( !$message->save() ) 
    {
      Log::error('message not saved');
      return Response::json(['error' => 'Message not sent. Please resubmit.']);
    }
  }

    public function updateMessage(Request $request)
    {
      Purifier::clean($request->all());
      $admin = Auth::user();

      if ($admin->roleID == 'Admin')
      {
        $message = Contact::where('id',$request->input('id'))->first();

        $check = $request->input('replied');
        if (isset($check))
        {
          $message->replied = $request->input('replied');
        }
        $check = $request->input('read');
        if (isset($check))
          $message->read = $request->input('read');

        $check = $request->input('resolved');
        if (isset($check))
        {
          if($check == 0)
          {
            $message->resolved = $request->input('resolved');
            $message->resolved_by = null;
            $message->resolved_at = null;
            $message->save();
          }
          else if ($check == 1)
          {
            $message->resolved = $request->input('resolved');
            $message->resolved_by = $admin->email;
            $message->resolved_at = Carbon::now();
            $message->save();
          }

      }
    }
  }
}
