<!DOCTYPE html>
<!--[if IE]><![endif]-->
<!--[if IE 8 ]><html dir="<?php echo $direction; ?>" lang="<?php echo $lang; ?>" class="ie8"><![endif]-->
<!--[if IE 9 ]><html dir="<?php echo $direction; ?>" lang="<?php echo $lang; ?>" class="ie9"><![endif]-->
<!--[if (gt IE 9)|!(IE)]><!-->
<html dir="<?php echo $direction; ?>" lang="<?php echo $lang; ?>">
<!--<![endif]-->
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo $title; ?></title>
  <base href="<?php echo $base; ?>" />
  <script src="catalog/view/javascript/jquery/jpos/jquery-2.1.1.min.js" type="text/javascript"></script>
  <link href="catalog/view/javascript/jquery/jpos/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen" />
  <script src="catalog/view/javascript/jquery/jpos/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
  <link href="catalog/view/javascript/jquery/jpos/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
  <link href="//fonts.googleapis.com/css?family=Open+Sans:400,400i,300,600,700" rel="stylesheet" type="text/css" />
  <link href="catalog/view/theme/default/stylesheet/jpos/stylesheet.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="catalog/view/javascript/jquery/jpos/scrollert/css/scrollert.min.css" />
  <script src="catalog/view/javascript/jquery/jpos/scrollert/js/scrollert.js"></script>
  <script type="text/javascript" src="catalog/view/javascript/jquery/jpos/jquery.fullscreen.min.js"></script>

  <link rel="stylesheet" type="text/css" href="catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css" />

  <?php if(VERSION <= '2.3.0.2') { ?>
  <script type="text/javascript" src="catalog/view/javascript/jquery/datetimepicker/moment.js"></script>
  <?php } else { ?>
  <script type="text/javascript" src="catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js"></script>
  <script type="text/javascript" src="catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js"></script>
  <?php } ?>

  <script type="text/javascript" src="catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js"></script>
  <link href="catalog/view/javascript/jquery/jpos/owl-carousel/owl.carousel.min.css" type="text/css" rel="stylesheet" media="screen" />
  <link href="catalog/view/javascript/jquery/jpos/owl-carousel/owl.theme.default.min.css" type="text/css" rel="stylesheet" media="screen" />
  <script src="catalog/view/javascript/jquery/jpos/owl-carousel/owl.carousel.js" type="text/javascript"></script>
</head>
<body class="<?php echo $class; ?>">
<main id="mainwrap">
  <div id="posmenu" class="posmenu">
    <div class="menu-header">
      <button id="button-menu"><i class="fa fa-dedent fa-lg"></i></button>
      <?php /* if(isset($user_info['def_location'])) { ?>
        <div class="location" title="<?php echo htmlspecialchars($user_info['def_location']); ?>"><?php echo $user_info['def_location']; ?></div>
      <?php } */ ?>
    </div>
    <div class="inner-posmenu">
      <ul class="list-unstyled">
        <?php if ($user_logged) { ?>
        <li class="psusername">
          <?php if ($user_info['thumb']) { ?>
          <img src="<?php echo $user_info['thumb']; ?>" alt="<?php echo $user_info['name']; ?>" />
          <?php } ?>
          <h3><?php echo $user_info['name']; ?></h3>
        </li>

        <li><a class="panel_close_all"><i class="fa fa-home"></i><span><?php echo $text_home; ?></span></a></li>
        <li><a class="order-history panel_show" data-panel="#order-history-wrap"><i class="fa fa-history"></i><span><?php echo $text_order_list; ?></span></a></li>
        <?php /* <!-- <li><a class="on-hold-items panel_show" data-panel="#ps-order-onhold"><i class="fa fa-folder-o"></i><?php echo $text_order_onhold; ?></a></li> --> */ ?>
        <li><a class="customer-list-open panel_show" data-panel="#ps-customer-list"><i class="fa fa-user"></i><span><?php echo $text_customers_list; ?></span></a></li>
        <li><a class="general-open panel_show" data-panel="#ps-general-wrap"><i class="fa fa-cog"></i><span><?php echo $text_general; ?></span></a></li>
        <li><a class="account-open panel_show" data-panel="#ps-account-wrap"><i class="fa fa-lock"></i><span><?php echo $text_account; ?></span></a></li>

        <li><a class="user_logout"><i class="fa fa-sign-out"></i><span><?php echo $text_logout; ?></span></a></li>
        <?php } else { ?>
        <li><a class="login-open login_panel panel_show" data-panel="#ps-login"><i class="fa fa-sign-in"></i><span><?php echo $text_login; ?></span></a></li>
        <?php } ?>
      </ul>
      <span class="checkout-success panel_show hide" style="display: none !important;" data-panel="#ps-checkout-success"></span>
    </div>
  </div>