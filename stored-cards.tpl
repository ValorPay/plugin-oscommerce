{use class="frontend\design\Info"}
{\frontend\design\Info::addBoxToCss('table-list')}
{if $success_message}
    <div class="messages"><div class="message success">{$success_message}</div></div>
{/if}
<div class="account-history">
    {if $total_count > 0}
        <div class="main">
            <table class="order-info orders-table table-list">
                <tr class="headings">
                    <th class="orders-id">Card Number</th>
                    <th class="shipped-to">Card Holder Name</th>
                    <th class="products">Type</th>
                    <th class="links"></th>
                </tr>
                {foreach $payment_profile as $profile}
                    <tr class="item">
                        <td class="orders-id">
                            <span class="order-number">xx-{$profile["masked_pan"]}</span>
                        </td>
                        <td class="shipped-to name">
                            <span class="order-number">{$profile["cardholder_name"]}</span>
                        </td>
                        <td class="products">
                            <span class="order-number"><img width="75px" src="{$brand_logo_path}/{if $profile["card_brand"]=="Unknown"} Diners {else} { strtolower($profile["card_brand"]) } {/if}.png" title="{if $profile["card_brand"]=="Unknown"} Diners {else} { strtolower($profile["card_brand"]) } {/if}" /></span>
                        </td>
                        <td class="links td-alignright">
                            <a class="view_link" data-js-confirm="Are you sure you want to delete the card ending in {$profile["masked_pan"]}?" href="{$profile["delete_url"]}">Delete</a>
                        </td>
                    </tr>
                {/foreach}
            </table>
        </div>
        <script type="text/javascript">
            tl('{Info::themeFile('/js/main.js')}', function(){

                if ( typeof alertMessage !== 'function' ) return;
                $('a[data-js-confirm]').on('click', function () {
                    alertMessage('<p>'+$(this).attr('data-js-confirm')+'</p><div><a class="btn" href="'+$(this).attr('href')+'">{$smarty.const.IMAGE_BUTTON_CONTINUE|escape:javascript}</a></div>');
                    return false;
                });

            })
        </script>
    {else}
        <div class="noItems">You have not yet have any stored cards.</div>
    {/if}
</div>