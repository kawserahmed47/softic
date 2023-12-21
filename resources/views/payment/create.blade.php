<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>Softic Payment Gateway</title>
  </head>
  <body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-md-offset-5 align-center">
                <div class="card mt-5">
                    <div class="card-header">
                      Make a payment
                    </div>
                    <div class="card-body">
                        @if (Session::get('error_message'))
                            <p class="text-danger bold">{{Session::get('error_message')}}</p>
                        @endif
                        @if (Session::get('success_message'))
                            <p class="text-success bold">{{Session::get('success_message')}}</p>
                        @endif
                        <form method="POST" action="{{route('payment.store')}}" id="make-payment-form">
                            @csrf

                            <div class="alert alert-dismissible d-none" role="alert">
                                <strong class="message">Payment Success!</strong>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>

                            <div class="mb-3">
                              <label for="amount" class="form-label">Amount</label>
                              <input type="text" required name="amount" class="form-control" id="amount">
                              <small class="error text-danger error-amount"></small>
                            </div>
                            <div class="mb-3">
                              <label for="user_id" class="form-label">Pay as</label>
                              <select class="form-control" required name="user_id" id="user_id">
                                <option value="">Select User</option>
                                @if (count($users))
                                    @foreach ($users as $user)
                                        <option value="{{$user->id}}">{{$user->name}}</option>
                                    @endforeach
                                @endif
                              </select>
                              <small class="error text-danger error-user_id"></small>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>








    <!-- Optional JavaScript; choose one of the two! -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>


    <script>
        $(document).on('submit', '#make-payment-form', function(e){
            e.preventDefault()
            let _this = $(this);
            let _this_submit_btn = _this.find('button[type="submit"]');
            let _this_submit_btn_html = _this_submit_btn.html();
            let _this_alert = _this.find('.alert');

            $.ajax({
                type: _this.attr('method'),
                url: _this.attr('action'),
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: _this.serialize(),
                beforeSend: function() {
                    _this_alert.removeClass('alert-warning alert-success fade show').addClass('d-none')
                    _this_submit_btn.html("Loading...");
                    _this_submit_btn.prop('disabled', true);
                },
                success: function (response) {
                    // _this_submit_btn.prop('disabled', false);
                    _this_submit_btn.html(_this_submit_btn_html);
                    _this_alert.removeClass('d-none').addClass('alert-success fade show');
                    _this_alert.find('.message').text(response.message);
                    location.href= response.stripe_response.url;
                },
                error: function(xhr, status, error) {
                    _this_submit_btn.prop('disabled', false);
                    _this_submit_btn.html(_this_submit_btn_html);
                    let responseText = $.parseJSON(xhr.responseText);
                    _this_alert.removeClass('d-none').addClass('alert-warning fade show');
                    _this_alert.find('.message').text(responseText.message);

                    $.each(responseText.errors, function (i, error) {
                        _this.find('.error-'+i).text(error[0]);
                    });
                }
            });
        })
    </script>


  </body>
</html>
