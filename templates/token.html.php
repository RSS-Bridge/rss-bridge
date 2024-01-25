<?php
/**
 * This template renders a form for user to enter a auth token if it's enabled
 */

?>

<h1>
    Authentication with token required
</h1>

<p>
    <?= e($message) ?>
</p>

<form action="" method="get">
    <label for="token">Token:</label>
    <input type="password" name="token" id="token" placeholder="token">
    <input type="submit" value="OK">
</form>
