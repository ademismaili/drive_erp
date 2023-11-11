<?php

include 'templates/header.php';


// Admin user have role id equal to 3,
// and we will omit admin from this result.
$tg = app('db')->select(
    "SELECT `tg_codes`
    FROM `tg_data`
    LIMIT 10000;"
);

?>

<link rel="stylesheet" href="assets/css/dataTables.bootstrap.css"/>

<div class="row">
    <?php
        $sidebarActive = 'users';
        require 'templates/sidebar.php';
    ?>

    <div class="col-md-9 col-lg-10">
        <div class="ajax-loading d-flex flex-column align-items-center pt-4 pb-4" id="loading-users">
            <i class="fa fa-2x fa-spinner fa-spin"></i>
            <div class="mt-2"><?= trans('loading') ?></div>
        </div>

        <div class="mt-5">
            <table class="table table-striped w-100" id="users-list" style="display: none;">
                <thead>
                    <tr>
                        <th>test</th>
                    </tr>
                </thead>
                <?php foreach ($tg as $tgs) : ?>
                    <tr class="user-row">
                        <td><?= $tgs['tg_codes'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

        <script src="assets/js/vendor/sha512.js"></script>
        <?php include 'templates/footer.php'; ?>
        <script src="assets/js/vendor/jquery.dataTables.min.js"></script>
        <script src="assets/js/vendor/dataTables.bootstrap5.js"></script>
        <script src="assets/js/app/users.js"></script>
    </body>
</html>