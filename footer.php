<?php if(if_group("superadmin")) { ?>
  <br /><br /><br /><a href="githook.php">Check for app updates</a>
<?php
}

?>
<script type="text/javascript">
function copy(data) {
    navigator.clipboard.writeText(data).then(() => {
        // TODO: positive feedback
    }).catch((e) => {
        // TODO: negative feedback
    });
}
</script>
<?php
require_once "resources/footer.php";
