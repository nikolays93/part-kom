/**
 * global partkom_data = {url, nonce}
 */
jQuery(document).ready(function($) {

    /**
     * Result table Class
     */
    function resultTable() {
        this._row = '';
        this.$table = $('.result table');
    }

    resultTable.prototype.start_row = function( rowid, ttag ) {
        if( ttag ) this._row += '<' + ttag + '>';
        this._row += rowid ? '<tr id="'+rowid+'">' : '<tr>';
    }

    resultTable.prototype.end_row = function( ttag ) {
        this._row += '</tr>';
        if( ttag ) this._row += '</' + ttag + '>';
        this.$table.append( this._row );
        this._row = '';
    }

    resultTable.prototype.fill_row = function(fill, fieldname, tagName) {
        var className = '';
        if(!tagName) tagName = 'td';
        if( fieldname ) className += ' class="' + fieldname + '"';

        this._row += '<'+ tagName + className +'>' + fill + '</'+ tagName +'>'
    }

    resultTable.prototype.fill_buybtn = function( fields ) {
        // detailNum(order) == number(search)
        // comment(order)
        // reference(order)
        var needFields = ['number', 'maker', 'makerId', 'description', 'price', 'providerId', 'quantity'];

        var data = '';
        $.each(fields, function(key, field) {
            if( 0 <= needFields.indexOf(key) ) {
                data += ' data-' + key.toLowerCase() + '="' + field + '"';
            }
        });

        var buybtn = $()

        this.fill_row('<a href="#"' + data + '>Купить</a>');
    }

    resultTable.prototype.clear_table = function() {
        var $parent = this.$table.parent();
        var classes = this.$table.attr('class');

        this.$table.remove();
        this.$table = $('<table></table>').attr('class', classes).appendTo( $parent );
    }

    resultTable.prototype.insert_message = function(message, className) {
        this.start_row();
        this.fill_row(message, className);
        this.end_row();
    }

    var resultTable = new resultTable();
    var sortArgs = [[4,0], [3,1]];

    /**
     * Search form
     * Get products table
     */
    $('#search_parts').on('submit', function(event) {
        event.preventDefault();
        var $form = $(this);

        resultTable.clear_table();
        resultTable.insert_message('Загрузка..');

        $.ajax({
            type: 'GET',
            url: partkom_data.url,
            data: {
                number: $('[name="number"]', $form).val(),
                action: 'search_parts',
                nonce: partkom_data.nonce
            },

            success: function(data) {
                var arData = JSON.parse( data );

                resultTable.clear_table();

                if( !arData.length ) {
                    resultTable.insert_message('По вашему запросу ничего не найдено');
                }

                $.each(arData, function(index, fields) {
                    // $.each(fields, function(i, v) {
                        //     switch (i) {
                        //         case 'number':
                        //         case 'maker':
                        //         case 'makerId':
                        //         case 'description':
                        //         case 'providerId':
                        //         case 'providerDescription':
                        //         case 'minQuantity':
                        //         case 'storehouse':
                        //         case 'expectedDays':
                        //         case 'expectedDate':
                        //         case 'guaranteedDays':
                        //         case 'guaranteedDate':
                        //         case 'lastUpdateDate':
                        //         case 'statProvider':
                        //         case 'price':
                        //         case 'quantity':
                        //         case 'detailGroup':
                        //         case 'group':
                        //         case 'placement':
                        //         case 'placementId':
                        //         case 'orderDate':
                        //         case 'lastOrderDate':
                        //         case 'statSuccessPercent':
                        //         case 'statRefusalPercent':
                        //         case 'customerPickup':
                        //     }
                        // });

                    if( 0 == index ) {
                        resultTable.start_row(null, 'thead');
                        resultTable.fill_row('Производитель', 'maker', 'th');
                        resultTable.fill_row('Описание', 'description', 'th');
                        resultTable.fill_row('Цена', 'quantity', 'th');
                        resultTable.fill_row('Кол-во', 'lastUpdateDate', 'th');
                        resultTable.fill_row('Cрок поставки', 'expectedDays', 'th');
                        resultTable.fill_row('Последнее обновление', 'price', 'th');
                        resultTable.fill_row('', 'spacer', 'th');
                        resultTable.end_row('thead');
                    }

                    var sumprice = parseFloat(fields[ 'price' ]) + (parseFloat(fields[ 'price' ]) / 100 * partkom_data.percent);
                    fields[ 'price' ] = parseInt( sumprice );

                    resultTable.start_row();
                    resultTable.fill_row(fields[ 'maker' ], 'maker');
                    resultTable.fill_row(fields[ 'description' ], 'description');
                    resultTable.fill_row(fields[ 'price' ] + ' руб.', 'price');
                    resultTable.fill_row(fields[ 'quantity' ] + ' шт.', 'quantity');
                    resultTable.fill_row(fields[ 'expectedDays' ], 'expectedDays');
                    resultTable.fill_row(fields[ 'lastUpdateDate' ], 'lastUpdateDate');
                    resultTable.fill_buybtn( fields );
                    resultTable.end_row();
                });

                resultTable.$table.tablesorter( {sortList: sortArgs} );
            }
        }).fail(function() {
            console.log('jQuery ajax fail!');
        });
    });

    var orderTarget = '#order';
    var $target = $(orderTarget);
    var arOrder = {
        'name': $('#order__product-name', $target),
        'sku': $('#order__product-sku span', $target),
        'manufacturer': $('#order__product-manufacturer span', $target),
        'max_qty': $('#order__product-max_qty span', $target),
        'price': $('#order__product-price span', $target),
        'summary': $('#order__product-summary span', $target),
        'qty': $('#order__qty-input input', $target),

        'user_name': $('#order__user-name input', $target),
        'user_email': $('#order__user-email input', $target),
        'user_phone': $('#order__user-phone input', $target),

        'comments': $('#order__comments textarea', $target),

        'makerId': $('[name="makerId"]', $target),
        'providerId': $('[name="providerId"]', $target),
    }

    var $privacyCheckbox = $('[name="privacy_terms"]', $target);
    var $orderSubmit = $('[type="submit"]', $target);

    /**
     * Open order modal
     */
    resultTable.$table.on('click', '[data-number]', function(event) {
        event.preventDefault();

        var $self = $(this);

        arOrder.name.text( $self.data('description') );
        arOrder.sku.text( $self.data('number') );
        arOrder.manufacturer.text( $self.data('maker') );
        arOrder.manufacturer.data('makerid', $self.data('makerid'));
        arOrder.max_qty.text( $self.data('quantity') );
        arOrder.price.text( $self.data('price') );

        arOrder.makerId.val( $self.data('makerid') );
        arOrder.providerId.val( $self.data('providerid') );

        /**
         * @todo add min qty
         */

        arOrder.qty.attr('max', $self.data('quantity'));
        arOrder.qty.on('change', function(event) {
            arOrder.summary.text( $self.data('price') * arOrder.qty.val() );
        }).trigger('change');

        $.fancybox.open({
            src  : orderTarget,
            type : 'inline'
        });
    });

    /**
     * Request new order
     */

    $privacyCheckbox.on('change', function(event) {
        if( $(this).is(':checked') ) {
            $orderSubmit.removeAttr('disabled');
        }
        else {
            $orderSubmit.attr('disabled', 'disabled');
        }
    }).trigger('change');

    $('#order_request').on('submit', function(event) {
        event.preventDefault();
        var $form = $(this);
        $orderSubmit.attr('disabled', 'disabled');
        $('#result_mesage', $form).hide();

        function insert_error( errs ) {
            var $res = $('#result_mesage', $form)
                .html('')
                .css('color', 'red');

            $.each(errs, function(index, err) {
                $res.append('<p>' + err + '</p>');
            });

            $res.fadeIn();
        }

        function success_order( arData ) {
            try {
                if( arData.success.length ) {
                    resultTable.clear_table();
                    resultTable.insert_message( arData.success );
                    return true;
                }
            } catch(e) {
                console.log(e);
            }

            try {
                if( arData.errors.length ) {
                    insert_error(arData.errors);
                    return false;
                }
            } catch(e) {
                console.log(e);
            }

            insert_error(arData.errors);
            return false;
        }

        $.ajax({
            type: 'POST',
            url: partkom_data.url,
            data: {
                user: {
                    'first_name': arOrder.user_name.val(),
                    'last_name': '',
                    'email': arOrder.user_email.val(),
                    'phone': arOrder.user_phone.val(),
                },
                items: [{
                    'name': arOrder.name.text(),
                    'price': parseInt(arOrder.price.text()),
                    'qty': arOrder.qty.val(),
                    'metas': {
                        'sku': arOrder.sku.text(),
                        'makerId': arOrder.makerId.val(),
                        'providerId': arOrder.providerId.val(),
                    },
                },
                ],
                ordermetas: {
                    'comments': arOrder.comments.val()
                },
                action: 'partkom_create_wc_order',
                nonce: partkom_data.nonce
            },

            success: function(data) {
                var arData = JSON.parse( data );

                var success = success_order( arData );

                if( success ) {
                    $.fancybox.close();
                }

                $orderSubmit.removeAttr('disabled');
            }

        }).fail(function() {
            // console.log('jQuery ajax fail!');
        });
    });
});
