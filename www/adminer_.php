<?php
function adminer_object() {

    class AdminerSoftware extends Adminer {

        function permanentLogin() {
            // key used for permanent login
            return '9c224707c97be4c802f26f996a9d084d';
        }

    }

    return new AdminerSoftware;
}

include './adminer.php';