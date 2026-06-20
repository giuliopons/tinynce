<!DOCTYPE html>
<head>
	<!--

		LOGIN LAYOUT TEMPLATE

		Basic instructions
		==================
		You can change this html as you prefer, just don't touch strings
		between graphs {  and  } which are used for translation by AdAdmin.
		Pay attention (don't touch) also to # symbols which are used to build
		smart tags that are changed from AdAdmim.

	-->
	
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="robots" content="noindex">

	<style>
		/* promo link to codecanyon */
		#promo {position:fixed;right:0;bottom:0;display:inline-block;padding:5px 10px}
	</style>
	
	##JQUERYINCLUDE##
	<script language="JavaScript" src="##root##src/template/comode.js?v=##VER##"></script>

	<title>{Timy login page}</title>
	<link rel="manifest" href="##root##data/timy-theme/manifest.json">

</head>
<body onload="document.forms[0].##usernamevar##.focus();" class="nomenu">



	<form method="post" action="##actionurl##" id='loginform' name='loginform'>
		<input type="hidden" name="redirectUrl" value="##redirectUrl##">
		<table>
			<tr>
				<td class="logo">
				<img src="##LOGO##?1" id="logo">
				Timy ##VER##</td>
			</tr>
			<tr>
				<td>{user}<br/>
				<input name="##usernamevar##" type="text" maxlength="20" class='f'></td>
			</tr>
			<tr>
				<td>{password}<br/>
				<input name="##passwordvar##" type="password" maxlength="20" class='f' onkeypress="submitonenter('loginform',event,this)"></td>
			</tr>
			<tr>
				<td><a class="btn" href="javascript:;" onclick="document.forms[0].submit()">{Login}</a></td>
			</tr>
			<tr>
				<td>
					<div class="message">##msg##</div>
					<br>

					<!-- FORGOT PASS procedure link -->
					<a href="##root##src/resetpassword.php" ##hiderecover##>&raquo; {forgot password?}</a>
					
					<!-- DEMO BUTTON, you can remove in your custom theme -->
					<a id='demohere' href="javascript:;" onclick="show('alertBox');" class="btn" style="float:right;display:none">DEMO HERE</a>
					
					<!-- SIGN IN is available if payments are ON -->
					<div id="signin"  ##hidesignin##>
						<a href="##root##src/componenti/gestioneutenti/signin.php">&raquo; {Sign in}</a>
					</div>

				</td>
		</table>
	</form>


</body>
</html>