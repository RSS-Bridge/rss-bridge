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

<form action="" method="get" autocomplete="off">
    <label for="token">Token:</label>
    <input type="text" name="token" id="token" placeholder="token" value="<?= e($token) ?>">
    <input type="submit" value="OK">
</form>
