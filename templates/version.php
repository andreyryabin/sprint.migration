<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 */

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $description = "<?php echo $description ?>";

    public function up()
    {
        //your code ...
    }

    public function down()
    {
        //your code ...
    }
}