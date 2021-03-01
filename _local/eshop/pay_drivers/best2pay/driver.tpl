%%include_language "_local/eshop/pay_drivers/best2pay/driver.lng"%%

<!--#set var="settings_form" value="
<tr>
    <td colspan="2"><hr /></td>
</tr>
<tr>
    <td>%%b2p_sector_id%%</td>
    <td><input type="text" name="b2p_sector_id" class="field" value="##b2p_sector_id##" placeholder="%%b2p_sector_id_help%%"  /></td>
</tr>
<tr>
    <td>%%b2p_password%%</td>
    <td><input type="text" name="b2p_password" class="field" value="##b2p_password##" placeholder="%%b2p_password_help%%" /></td>
</tr>
<tr>
    <td>%%b2p_test_mode%%</td>
    <td>
        <label><input type="radio" name="b2p_test_mode" value="1"##IF(b2p_test_mode == '1')## checked="checked"##ENDIF## /> %%b2p_test_mode_yes%%</label>
        <label><input type="radio" name="b2p_test_mode" value="0"##IF(b2p_test_mode == '0')## checked="checked"##ENDIF## /> %%b2p_test_mode_no%%</label>
    </td>
</tr>
"-->

<!--#set var="checkout_form" value="
    <form name="paymentformbest2pay" action="##process_url##" method="post">
    ##hiddens##
    ##if(_button_html=="1")##
    ##button##
    ##else##
    <input type="submit" name="sbmt" class="btn" value="      ##button_name##      " ##disabled##>
    ##endif##
    </form>
"-->

<!--#set var="pay_form" value="
    <form name="paymentform" action="##url##" method="post">
    <input type="hidden" name="sector" value="##b2p_sector_id##">
    <input type="hidden" name="id" value="##b2p_order_id##">
    <input type="hidden" name="signature" value="##b2p_signature##">
    </form>
    <script type="text/javascript">
            document.paymentform.submit();
    </script>
"-->
