<?php if (isset($exception) && $exception instanceof Throwable) { ?>
    <div class="sp-col">
        <?= sprintf(
                "[%s] %s (%s) in %s:%d \n",
                get_class($exception),
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getFile(),
                $exception->getLine()
        ); ?>
    </div>
<?php } ?>
