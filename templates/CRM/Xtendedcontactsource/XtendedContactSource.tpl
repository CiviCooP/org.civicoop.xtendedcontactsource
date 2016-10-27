
<div class = "extended_contact_source-section">
  <div class="crm-summary-row ">
    <div class="crm-label">{ts}Extended Contact Source{/ts}</div>
    <div class="crm-content crm-xtended-contact_source">{$xtendedContactSource}</div>
  </div>
</div>

{literal}
  <script>
    cj(function($){
      $(".crm-summary-contactinfo-block").children().children().children().append($(".extended_contact_source-section").html());
      $(".extended_contact_source-section").remove();
    });
  </script>
{/literal}