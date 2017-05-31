<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

   </head>
    <body>
      <form action="/your-server-side-code" method="POST">
        <script
          src="https://checkout.stripe.com/checkout.js" class="stripe-button"
          data-key="pk_test_rJKqFRMRduGAyguxdWT2TfcI"
          data-amount="999"
          data-name="Pyxis"
          data-description="Subscribe"
          data-image="https://stripe.com/img/documentation/checkout/marketplace.png"
          data-locale="auto">
        </script>
        <a href="http://localhost:3000/About">react</a>
</form>
   </body>
</html>
