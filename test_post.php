<html>
<?php
if (count($_POST) > 0)
{
    foreach ($_POST as $key => $value){
        echo $key.":".urldecode($value).PHP_EOL;
    }
} else
{
    ?>
    <form method="POST" action="test_post.php">
        <input type="checkbox" name="checkbox" value="chk">Check<br/>
        <input name="firstname" placeholder="firstname" /><br/>
        <input name="lastname"  placeholder="lastname"/><br/>
        <input name="phone"  placeholder="phone"/><br/>
        <input name="postalCode"  placeholder="postalCode"/><br/>
        <input name="address"  placeholder="address"/><br/>
        <input name="email"  placeholder="email"/><br/>
        <input name="birthdate" type="date" placeholder="birthdate"/><br/>
        <input name="civility" type="radio" value="m">M<br/>
        <input name="civility" type="radio" value="mme">Mme<br/>

        <select name="options">
            <option value="option1">option1</option>
            <option value="option2">option2</option>
        </select><br/>
        <input type="submit" value="Envoyer"/><br/>
    </form>
<?php
}
?>
</html>