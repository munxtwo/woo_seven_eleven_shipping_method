<?php
    /*
     * Reads the response returned from the 7-11 API and sets the values accordingly.
     */
    header("Content-Type:text/html; charset=utf-8");
    $SevenStoreId   = $_REQUEST['storeid'];
    $SevenStoreName = $_REQUEST['storename'];
    $SevenAddress = $_REQUEST['address'];
?>

<script type="text/javascript">
    window.opener.document.getElementById("storeid").value = "<?php echo $SevenStoreId;?>";
    window.opener.document.getElementById("store_id").value = "<?php echo $SevenStoreId;?>";
    window.opener.document.getElementById("store_name").value = "<?php echo $SevenStoreName;?>";
    window.opener.document.getElementById("store_name_field").style.display = 'block';
    window.opener.document.getElementById("store_address").value = "<?php echo $SevenAddress;?>";
    window.opener.document.getElementById("store_address_field").style.display = 'block';
    window.close();
</script>
