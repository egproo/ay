<?php if ($categories) { ?>
  <div class="psrefine">
    <?php if (count($categories) <= 5) { ?>
      <div class="row">
        <div class="col-sm-2"><h3><?php echo $text_refine; ?></h3></div>
        <div class="col-sm-10">
          <ul class="list-inline">
            <?php foreach ($categories as $category) { ?>
            <li><a class="category category_products" data-path="<?php echo $category['path']; ?>"><?php echo $category['name']; ?></a></li>
            <?php } ?>
          </ul>
        </div>
      </div>
    <?php } else { ?>
      <div class="row">
        <div class="col-sm-12">
          <h3><?php echo $text_refine; ?></h3>
        </div>
        <?php foreach (array_chunk($categories, ceil(count($categories) / 4)) as $categories) { ?>
        <div class="col-sm-3">
          <ul class="list-unstyled psparts">
            <?php foreach ($categories as $category) { ?>
            <li><a class="category category_products" data-path="<?php echo $category['path']; ?>"><?php echo $category['name']; ?></a></li>
            <?php } ?>
          </ul>
        </div>
        <?php } ?>
      </div>
    <?php } ?>
  </div>
<?php } ?>