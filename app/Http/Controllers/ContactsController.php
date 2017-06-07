<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Contact;
use Response;
use Purifier;

class ContactsController extends Controller
{
  // store message
  public function StorMessage() {
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
  }

  public function message() {
    $message = new Contact;
    $message->email = 'qed@mail.com';
    $message->name = 'qed boo';
    $message->message = 'this is a message';

    if( !$message->save()) {
      Log::error('message saved');
    }
  }

}
