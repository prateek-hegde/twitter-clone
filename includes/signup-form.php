<?php
if(isset($_POST['signup']) && !empty($_POST['signup'])){
	$screenName = $_POST['screenName'];
	$email      = $_POST['email'];
	$password   = $_POST['password'];
	$error      = '';

	if(!empty($email) && !empty($screenName) && !empty($password)){
		$email 		= $getUser->checkInput($email);
		$screenName = $getUser->checkInput($screenName);
		$password 	= $getUser->checkInput($password);

		if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			$error = 'Invalid email format!';
		} else if(strlen($screenName) > 20) {
			$error = 'Name must be less than 20 charecters!';
		} else if(strlen($password) < 6){
			$error = 'Password should contain more than 6 charecters!';
		} else if($getUser->checkEmail($email) === true) {
			$error = 'This email already exists';
		} else {
			$password = $getUser->securedPassword($password);
			$user_id = $getUser->create('users', array('email' => $email, 'password' => $password, 'screenName' => $screenName ));
			$_SESSION['user_id'] = $user_id;
			header('Location: ./includes/signup.php?step=1');
		}
	} else {
		$error = 'All fields are required';
	}
}
?>
<form method="post">
<div class="signup-div"> 
	<h3>Sign up </h3>
	<ul>
		<li>
		    <input type="text" name="screenName" placeholder="Full Name" value="<?php if(isset($screenName)){echo $screenName; } ?>" />
		</li>
		<li>
		    <input type="email" name="email" placeholder="Email" value="<?php if(isset($email)){echo $email; } ?>" />
		</li>
		<li>
			<input type="password" name="password" placeholder="Password"/>
		</li>
		<li>
			<input type="submit" name="signup" Value="Signup for Twitter">
		</li>
	</ul>
	
	 <?php
	 	if(isset($error)){
	 		echo '<li class="error-li">
				  	<div class="span-fp-error">'.$error.'</div>
				  </li>';
	 	}
	 ?> 
	
</div>
</form>