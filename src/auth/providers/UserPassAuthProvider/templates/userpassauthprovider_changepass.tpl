<H1>Change Password</H1>

<form id="changepass" method="post" action="{{ url('/users/changepass') }}">
	<input type="hidden" name="csrftoken" value="{{csrftoken}}">
	<div class="form-group row">
		<label for="password" class="col-3 col-form-label">Password</label>
		<div class="col-9">
			<input class="form-control" type="password" value="" id="password" name="password">
		</div>
	</div>

	<div class="form-group row">
		<label for="confirmpassword" class="col-3 col-form-label">Confirm Password</label>
		<div class="col-9">
			<input class="form-control" type="password" value="" id="confirmpassword" name="confirmpassword">
		</div>
	</div>
</form>

<button type="button" data-action="cancel" class="btn btn-primary" data-dismiss="modal">Cancel</button>
<button type="button" data-action="ok" class="btn btn-success">Change Password</button>


<script src="{{ url('assets/userpassauthprovider_changepass.js') }}"></script>
<link href="{{ url('assets/userpassauthprovider_style.css') }}" rel="stylesheet">
