<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title></title>
</head>
<body>
付款成功
<input id="order_sn" type="hidden" value="<?php echo $_GET['order_sn'];?>" />
</body>
<script>
    console.log($('#order_sn').val());
    //window.location.href="http://www.baidu.com";

</script>
</html>