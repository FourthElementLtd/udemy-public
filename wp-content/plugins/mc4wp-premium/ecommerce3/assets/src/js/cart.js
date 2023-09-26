'use strict';

const checkoutForm = document.querySelector('form.woocommerce-checkout, form[name="checkout"]');
let previousEmailAddress = '';
let previousDataString;
let formSubmitted = false;
const ajaxurl = typeof(mc4wp_ecommerce_cart) !== "undefined" ? mc4wp_ecommerce_cart.ajax_url : woocommerce_params.ajax_url;

function isEmailAddressValid(emailAddress) {
   const regex = /\S+@\S+\.\S+/;
   return typeof(emailAddress) === "string" && emailAddress !== '' && regex.test(emailAddress);
}

function getFieldValue(fieldName) {
   let field = checkoutForm.querySelector(`[name="${fieldName}"]`);
   if (!field) {
      return '';
   }

   return field.value.trim();
}

function sendFormData(async) {
   let data = {
      previous_billing_email: previousEmailAddress,
      billing_email:  getFieldValue('billing_email'),
      billing_first_name:  getFieldValue('billing_first_name'),
      billing_last_name:  getFieldValue('billing_last_name'),
      billing_address_1:  getFieldValue('billing_address_1'),
      billing_address_2:  getFieldValue('billing_address_2'),
      billing_city:  getFieldValue('billing_city'),
      billing_state:  getFieldValue('billing_state'),
      billing_postcode:  getFieldValue('billing_postcode'),
      billing_country:  getFieldValue('billing_country'),
   };
   const dataString = JSON.stringify(data);

   // schedule cart update if email looks valid && data changed.
   if( isEmailAddressValid(data.billing_email) && dataString !== previousDataString ) {
      const request = new XMLHttpRequest();
      request.open('POST', ajaxurl + "?action=mc4wp_ecommerce_schedule_cart", async);
      request.setRequestHeader('Content-Type', 'application/json');
      request.send(dataString);

      previousDataString = dataString;
      previousEmailAddress = data.billing_email;
   }
}

if( checkoutForm ) {
   checkoutForm.addEventListener('change', function() {
      sendFormData(true);
   });

   checkoutForm.addEventListener('submit', function() {
      formSubmitted = true;
   });

   // always send before unloading window, but not if form was submitted
   window.addEventListener('beforeunload', function() {
      if( ! formSubmitted ) {
         sendFormData(false);
      }
   });
}

