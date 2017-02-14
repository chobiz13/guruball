<?php include 'header.php' ?>

<div id="login">
	<form method="post" action-xhr="example" target="_blank">
	    <fieldset>
	        <label>
	            <span>Username</span>
	            <input type="text" name="name" required>
	        </label>
	        <label>
	            <span>Password</span>
	            <input type="text" name="password" required>
	        </label>
	        <input type="submit" value="Sign in">
	    </fieldset>
	</form>
</div>

<?php include 'footer.php' ?>