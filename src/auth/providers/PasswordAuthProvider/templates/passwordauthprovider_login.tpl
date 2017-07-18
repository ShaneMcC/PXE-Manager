<div class="container">
<form class="form-signin small" method="post">
  <input type="hidden" name="csrftoken" value="{{csrftoken}}">

  <h1 class="form-signin-heading">Please Enter Password</h1>
  <label for="inputPassword" class="sr-only">Password</label>
  <input type="password" name="pass" id="inputPassword" class="form-control" placeholder="Password" required>

  <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>

</form>
</div>

  <link href="{{ url('assets/passwordauthprovider_style.css') }}" rel="stylesheet">
