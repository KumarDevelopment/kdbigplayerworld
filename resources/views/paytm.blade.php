<head>
      <title>Show Payment Page</title>
   </head>
   <body>
      <center>
         <h1>Please do not refresh this page...</h1>
      </center>
      <form method="post" action="https://securegw.paytm.in/theia/api/v1/showPaymentPage?mid={{ env('PAYTM_MERCHANT_ID') }}&orderId={{ $orderID }}" name="paytm">
         <table border="1">
            <tbody>
               <input type="hidden" name="mid" value="{{ env('PAYTM_MERCHANT_ID') }}">
               <input type="hidden" name="orderId" value="{{ $orderID }}">
               <input type="hidden" name="txnToken" value="{{ $data['body']['txnToken']}}">
            </tbody>
         </table>
         <script type="text/javascript"> document.paytm.submit(); </script>
      </form>
   </body>
</html>