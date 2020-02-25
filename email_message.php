<?php
   /*
   Plugin Name: Email and message button for buddypress
   Plugin URI: http://www.ambivert.tech/wordpress
   description: A plugin to send email and message to both users.
   Version: 1.2
   Author: Ambivert Technologies Private Limited
   Author URI: http://www.ambivert.tech
   License: GPL2
   */




function my_custom_button() {
 // your button here

  if ( is_user_logged_in() ) {
    $sms_limit  = sms_limit(get_current_user_id());

    //print_r($sms_limit);
    $sms_count = get_option('msg_count');
    $button_type= "submit";
    $onclick = "";
    if($sms_count <=$sms_limit[0])
    {
      $button_type = "button";
      $onclick = " data-toggle='modal' data-target='#myModal' ";
    }



    echo '<form id="msg_form_send" method="post"> <span class="ambivert"><button type="'.$button_type.'"  '.$onclick.'  class="btn btn-primary" style="cursor:pointer;">Contact</button></span>


    <input name="online_user" type="hidden" value="'.get_current_user_id().'" >

    <input name="profile_user" type="hidden" value="'.bp_displayed_user_id().'" >
    </form>

    ';
}

if(isset($_POST["online_user"]) and isset($_POST["profile_user"])){
   $msg91_field =  explode("," ,get_option("msg91_field_send"));
   $current_user = wp_get_current_user();
   $author_obj = get_user_by('id', bp_displayed_user_id());
   $message_for_profile="You viewed ".$author_obj->display_name." profile. ";
   $message_for_online=$current_user->user_login." has viewed your profile.";



   foreach($msg91_field as $result){

    //echo $result."<br/>";

    $fields =  get_xprofile_form_fields_single($result);
    //print_r( $fields );

     $message_for_profile .= $fields[0].":";
    $value = my_bp_get_users_by_xprofile( $result, bp_displayed_user_id() );
     $message_for_profile.= $value[0]." ";
     //$message_for_profile .= "<br/>";

   $message_for_online .=$fields[0].":";
    $value1 = my_bp_get_users_by_xprofile( $result, get_current_user_id() );
    $message_for_online.= $value1[0]." ";
   // $message_for_online .= "<br/>";


}

// $message_for_profile;


 //   $message_for_online;

   $user_info = get_userdata(bp_displayed_user_id());

   $user_profile_emal = $user_info->user_email;

   $all_meta_for_user = get_user_meta( bp_displayed_user_id() );
   //print_r($user_info);
   //print_r( $all_meta_for_user );

   $profile_number = my_bp_get_users_by_xprofile( get_option("get_mobile_number_field"), bp_displayed_user_id() );

   $own_number = my_bp_get_users_by_xprofile( get_option("get_mobile_number_field"), get_current_user_id() );
   //echo $profile_number[0];
   //echo $own_number[0];


   $check_email_enable = get_option("email_enable");
   if($check_email_enable == "on"){
      @mail($author_obj->user_email, "msg91 check", $message_for_online );

   }
   //wp_mail( "ambivert.tech@gmail.com", $subject, $message );
   //echo $num = $profile_number[0];

   $check_sms_enable = get_option("sms_enable");
   if(isset($check_sms_enable) && $check_sms_enable=="on"){



   $curl = curl_init();

   curl_setopt_array($curl, array(
     CURLOPT_URL => "https://control.msg91.com/api/postsms.php",
     CURLOPT_RETURNTRANSFER => true,
     CURLOPT_ENCODING => "",
     CURLOPT_MAXREDIRS => 10,
     CURLOPT_TIMEOUT => 30,
     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
     CURLOPT_CUSTOMREQUEST => "POST",
     CURLOPT_POSTFIELDS => "<MESSAGE>
    <AUTHKEY>".get_option('msg91_api')."</AUTHKEY>
    <SENDER>".get_option('msg91_sender_id')."</SENDER>

    <SMS TEXT='".$message_for_profile."' >
        <ADDRESS TO='91".$own_number[0]."'></ADDRESS>

    </SMS>
    <SMS TEXT='".$message_for_online."' >
        <ADDRESS TO='91".$profile_number[0]."'></ADDRESS>

    </SMS>

</MESSAGE>
",
     CURLOPT_SSL_VERIFYHOST => 0,
     CURLOPT_SSL_VERIFYPEER => 0,
     CURLOPT_HTTPHEADER => array(
       "content-type: application/xml"
     ),
   ));
   //print_r($curl);

   $response = curl_exec($curl);
   $err = curl_error($curl);

   curl_close($curl);

   if ($err) {
     //echo "cURL Error #:" . $err;
   } else {
    // echo $response."ambivert";


    global $wpdb;
    $sms_limit  = sms_limit(get_current_user_id());
    $sms_count = 1;
    if(isset($sms_limit) && !empty($sms_limit)){
      $sms_count = $sms_limit[0]+1;
      //echo "UPDATE `wp_sms_limit` SET sms_limit='".$sms_count."' WHERE `user_id`='".get_current_user_id()."'";

        $wpdb->update(
          'wp_sms_limit',
          array(
            'sms_limit' => $sms_count,  // string
          ),
          array( 'user_id' => get_current_user_id() , 'dooc' => date("Y-m-d") ),

        );


      /*$wpdb->query($wpdb->prepare("UPDATE `wp_sms_limit` SET `sms_limit`='".$sms_count."' WHERE `user_id`='".get_current_user_id()."'"));
  */
    }else{



        $wpdb->insert('wp_sms_limit', array(
                        'sms_limit' => $sms_count ,
                        'user_id'  =>   get_current_user_id(),
                        'dooc' => date("Y-m-d")
                       )
             );

      }


    echo "Check your SMS.";
   }


 }
 //  echo get_option('success_msg_txt');
}

?>

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>

      </div>
      <div class="modal-body" style="color:black;">
        <p>You reach the maximum limit of messages.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>

<?php

}
add_filter( 'bp_before_member_header_meta', 'my_custom_button' );


/*
add_submenu_page( 'admin_menu', 'Custom Menu', 'My Custom Menu', 'manage_options', 'my-custom-menu', __CLASS__ .'::menu_page_output' );

public function menu_page_output() {
    //Menu Page output code


    echo "<h1>Setting For plugin Text to phone.</h1>";
}

*/

/**
 * Sub menu class
 *
 * @author Mostafa <mostafa.soufi@hotmail.com>
 */
class Sub_menu {

    /**
     * Autoload method
     * @return void
     */
    public function __construct() {
        add_action( 'admin_menu', array(&$this, 'register_sub_menu') );
    }

    /**
     * Register submenu
     * @return void
     */
    public function register_sub_menu() {
        add_submenu_page(
            'options-general.php', 'Text setting', 'Text setting', 'manage_options', 'text-setting', array(&$this, 'submenu_page_callback')
        );
    }

    /**
     * Render submenu
     * @return void
     */
    public function submenu_page_callback() {
        echo '<div class="wrap">';
        echo '<h2>Text Setting</h2>';
        echo '</div>';

        if(isset($_POST["submit"])){
         /* $txt_count = trim($_POST["msg_count"]);
          $success = trim($_POST["success"]);
          $msg_count = get_option('msg_count');
          print_r($_POST);*/

          foreach($_POST as $key=>$value){
            if($key == "msg91_field_send"){
               $value = implode(",",$value);
            }
            //echo $key.$value."<br/>";
            $check_value = get_option($key);
            if(isset($check_value)){
              update_option( $key, $value );
            }else{
              add_option( $key, $value  );
            }
          }

          /*if(!isset($msg_count)){
            add_option( 'msg_count', $txt_count, '', 'yes' );
            add_option( 'success_msg_txt', $success, '', 'yes' );


          }else{
            update_option( 'msg_count', $txt_count );
            update_option( 'success_msg_txt', $success );

          }*/
        }

        ?>
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

        <form action="" method="post">
          <div class="form-group">
            <label for="email">Sender ID:</label>
            <input type="text" value="<?=(get_option('msg91_sender_id'))?get_option('msg91_sender_id'):"0";?>" name="msg91_sender_id" class="form-control" >
          </div>
          <div class="form-group">
            <label for="email">Api Key:</label>
            <input type="text" value="<?=(get_option('msg91_api'))?get_option('msg91_api'):"0";?>" name="msg91_api" class="form-control" >
          </div>
          <div class="form-group">
          <label for="email">Select the fields which users will receive by email and sms</label>
            </div>
          <?php
            $fields =  get_xprofile_form_fields('0');
            $msg91_field =  explode("," ,get_option("msg91_field_send"));
            foreach($fields as $results){

              if($results->id != "793"){
            ?>

               <div class="form-group">

                <input type="checkbox" value="<?=$results->id;?>"  <?php if (in_array($results->id, $msg91_field)) {echo "checked";} ?> name="msg91_field_send[]" class="form-control" > <?=$results->name?>
              </div>
            <?php
            }
          }


          ?>
          <div class="form-group">
            <label for="email">Select the mobile number filed from the xprofile:</label>
            <select name="get_mobile_number_field">
            <?php
            foreach($fields as $results){

              if($results->id != "793"){
            ?>

                <option value="<?=$results->id;?>"  <?php if ($results->id == get_option("get_mobile_number_field")){echo "selected";} ?> > <?=$results->name?></option>

            <?php
          }

            }


            ?>
          </select>

          </div>


          <div class="form-group">
            <label for="email">How much text you want to send every user:</label>
            Enable: <input type="checkbox" name="sms_enable" <?=(get_option("sms_enable"))?"checked":"";?>/> Limit :<input type="text" value="<?=(get_option('msg_count'))?get_option('msg_count'):"0";?>" name="msg_count" class="form-control" id="email">
          </div>
          <div class="form-group">
            <label for="email">How much emails you want to send every user:</label>
            Enable: <input type="checkbox" name="email_enable" <?=(get_option("email_enable"))?"checked":"";?> /> Limit :<input type="text" value="<?=(get_option('email_count'))?get_option('email_count'):"0";?>" name="email_count" class="form-control" >
          </div>
          <!--div class="form-group">
            <label for="pwd">Successfully send Text:</label>
            <input type="text" value="<?=(get_option('success_msg_txt'))?get_option('success_msg_txt'):"Success fully send";?>" name="success" class="form-control" id="pwd">
          </div-->

          <button type="submit" name="submit" value="submit" style="    border: 1px solid #ccc;" class="btn btn-default">Submit</button>
        </form>
        <?php
    }

}

new Sub_menu();
function get_xprofile_form_fields($field_id) {

    global $wpdb;

    $phone_number = $wpdb->get_results(
        $wpdb->prepare(
            "
                SELECT `id`,`name`
                FROM `{$wpdb->prefix}bp_xprofile_fields`
                WHERE `option_order` = %d

            "
            , $field_id

        )
    );
    //echo $wpdb->prepare("SELECT `id,name`FROM `{$wpdb->prefix}bp_xprofile_fields`WHERE `option_order` = %d", $field_id);
    return $phone_number;
}
function get_xprofile_form_fields_single($field_id) {

    global $wpdb;

    $phone_number = $wpdb->get_col(
        $wpdb->prepare(
            "
                SELECT `name`
                FROM `{$wpdb->prefix}bp_xprofile_fields`
                WHERE `id` = %d

            "
            , $field_id

        )
    );
    //echo $wpdb->prepare("SELECT `id,name`FROM `{$wpdb->prefix}bp_xprofile_fields`WHERE `option_order` = %d", $field_id);
    return $phone_number;
}

function my_bp_get_users_by_xprofile( $field_id, $value ) {

    global $wpdb;

    $phone_number = $wpdb->get_col(
        $wpdb->prepare(
            "
                SELECT `value`
                FROM `{$wpdb->prefix}bp_xprofile_data`
                WHERE `field_id` = %d
                    AND `user_id` = %s
            "
            , $field_id
            , $value
        )
    );

    return $phone_number;
}
function sms_limit( $user_id ) {

    global $wpdb;

    $phone_number = $wpdb->get_col(
        $wpdb->prepare(
            "
                SELECT `sms_limit`
                FROM `{$wpdb->prefix}sms_limit`
                WHERE `user_id` = '%d' AND `dooc` = '".date("Y-m-d")."'
            "
            , $user_id
        )
    );

    return $phone_number;
}

?>
