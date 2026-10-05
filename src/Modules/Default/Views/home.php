<?php $this->start('title') ?>
Welcome | Pano
<?php $this->end() ?>

<?php $this->start('content') ?>
<div class="card">
    <div class="logo">🚀</div>

    <div class="badge">Pano Framework</div>

    <h1>It works!</h1>

    <p>
        Your application is running successfully.<br>
        Start building your domains and take full control of the flow.
    </p>

    <div class="footer">
        <?= date("Y") ?> &copy; Powered by <span><a href="https://pano-php.github.io/"><?= $name ?></a><small> <?= $version ?></small> </span>
    </div>
</div>
<?php $this->end() ?>