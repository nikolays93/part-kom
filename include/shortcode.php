<?php

namespace NikolayS93\partkom;


add_shortcode( 'partkom-searchform', __NAMESPACE__ . '\partkom_search_form' );
function partkom_search_form( $atts = array(), $content = '' ) {
    $atts = shortcode_atts( array(
        'link' => '#',
    ), $atts, 'part-kom-search' );

    ?>
    <form id="search_parts">
        <div class="form-wrap">
            <div class="label">
                <label for="number">Поиск по коду</label>
            </div>
            <div class="input">
                <input type="text" id="number" name="number" placeholder="Артикул">
                <!-- <input type="text" name="producer" placeholder="Производитель"> -->
                <?php if( $atts['link'] ): ?>
                    <p class="tip">или Вы можете сформировать <a href="<?=esc_url( $atts['link'] );?>">VIN-запрос</a></p>
                <?php endif; ?>
            </div>
            <div class="submit">
                <button type="submit">Найти</button>
            </div>
            <div class="after">
                <ul>
                    <li>- Актуальное наличие на складе</li>
                    <li>- Вводить только английские буквы</li>
                </ul>
            </div>
        </div>

        <div class="result">
            <table class="table table-hover"></table>
        </div>
    </form>
    <?php

    add_action( 'wp_footer', __NAMESPACE__ . '\partkom_ordermodal' );
}

function partkom_ordermodal() {
    ?>
    <div id="order" class="order" style="display: none">
        <form id="order_request">
            <h4 id="order__product-name"></h4>
            <div class="order__field" id="order__product-sku">Артикул: <span></span></div>
            <div class="order__field" id="order__product-manufacturer">Производитель: <span></span></div>
            <div class="order__field" id="order__product-max_qty">Доступное количество: <span></span></div>
            <div class="order__field" id="order__qty-input">Количество: <input type="number" name="qty" min="1" value="1"></div>
            <div class="order__field" id="order__product-price">Цена: <span></span> руб.</div>
            <div class="order__field" id="order__product-summary">Сумма: <span></span> руб.</div>

            <div class="order__field" id="order__user-name">Имя: <input type="text" name="user-name" placeholder="Введите ваше имя"></div>
            <div class="order__field" id="order__user-email">Email*: <input type="text" required="true" name="user-email" placeholder="Введите ваш email"></div>
            <div class="order__field" id="order__user-phone">Телефон*: <input type="text" required="true" name="user-phone" placeholder="Введите ваш номер телефона"></div>
            <div class="order__field" id="order__comments">
                Комментарии к закау:<br>
                <textarea name="comments"></textarea>
            </div>

            <label>
                <input type="checkbox" name="privacy_terms" value="1">
                Согласие и политика конфиденциальности
            </label>

            <br><br>
            <div class="order__submit submit"><input type="submit" value="Заказать"></div>

            <input type="hidden" name="makerId">
            <input type="hidden" name="providerId">

            <div id="result_mesage"></div>
        </form>
    </div>
    <?php
}

add_shortcode( 'partkom-partrequest', __NAMESPACE__ . '\partkom_part_request' );
function partkom_part_request( $atts = array(), $content = '' ) {
    $atts = shortcode_atts( array(
        'form_id' => '',
    ), $atts, 'partkom-partrequest' );

    if($form_id = absint($atts['form_id'])) {
        ?>
        <style>
            @media screen and (min-width: 515px) {
                .step .column {
                    float: left;
                    width: 50%;
                }
                .step .buttons {
                    clear: both;
                }
                .step label {
                    display: block;
                    width: 100%;
                    clear: both;
                }
                .step label + br {
                    display: none;
                }
                .step textarea {
                    width: 78.5%;
                }
                .step .btn + .btn {
                    margin-left: 10px;
                }
            }
        </style>
        <h4>СОЗДАНИЕ НОВОГО VIN ЗАПРОСА</h4>
        <ul>
            <li>Шаг 1. Информация об автомобиле</li>
            <li>Шаг 2. Запрашиваемые товары</li>
            <li>Шаг 3. Контактная информация</li>
        </ul>

        <?php echo do_shortcode( '[contact-form-7 id="'. $form_id .'"]' ); ?>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var $nextButton = $('<a href="#" class="btn btn-primary">Продолжить</a>');
                var $prevButton = $('<a href="#" class="btn btn-primary">Назад</a>');
                var $activeStep = false;

                function checkFillRequired() {
                    var ok = true;
                    $activeStep.find('[aria-required="true"]').each(function(index, el) {
                        var requiredText = 'Поле обязательно к заполнению';
                        var val = $(this).val();
                        if( !val || requiredText == val ) {
                            $(this).val( requiredText );

                            $(this).css({
                                'border-color': 'red',
                                'color': 'red',
                            });

                            $(this).one('click', function(event) {
                                $(this).val('');
                                $(this).css({
                                    'color': 'inherit',
                                });
                            });

                            $(this).one('keydown', function(event) {
                                $(this).css({
                                    'border-color': 'inherit',
                                });
                            });

                            ok = false;
                        }
                    });

                    return ok;
                }

                function setActiveStep( num ) {
                    /**
                     * to hide previous active step
                     */
                    if( $activeStep ) {
                        $activeStep
                            .removeClass('active')
                            .hide();
                    }

                    /**
                     * to active new step
                     */
                    $activeStep = $('.step[data-step="'+num+'"]');
                    $activeStep
                        .addClass('active')
                        .fadeIn();
                }

                $prevButton.on('click', function(event) {
                    event.preventDefault();

                    var step = $activeStep.data('step');
                    setActiveStep( --step );
                });

                $nextButton.on('click', function(event) {
                    event.preventDefault();

                    if( checkFillRequired() ) {
                        var step = $activeStep.data('step');
                        setActiveStep( ++step );
                    }
                });

                $('.wpcf7-submit').on('click', function(event) {
                    if( !checkFillRequired() ) {
                        return false;
                    }
                });

                $('.wpcf7').on('wpcf7mailsent', function(event) {
                    setActiveStep( 1 );
                });

                $('[data-step]').each(function(index, el) {
                    if( 0 != index ) {
                        $(el).hide();
                        $(el).find('.buttons').prepend( $prevButton.clone(1) );
                    }

                    if( index+1 != $('[data-step]').length ) {
                        $(el).find('.buttons').append( $nextButton.clone(1) );
                    }
                });

                setActiveStep( 1 );
            });
        </script>
        <?php
    }
}

// <div class="step" data-step="1">
//     <div class="column">
//         <h5>Основные данные</h5>
//         <label>VIN-код<br> [text VIN]</label>
//         <label>Год выпуска<br> [text YEAR]</label>
//         <label>Марка*<br> [text* BRAND]</label>
//         <label>Модель авто*<br> [text* MODEL]</label>
//         <label>Объем двигателя, см3<br> [text POTENCIA]</label>
//         <label>Мощность в л.с.<br> [text POWER]</label>
//     </div>
//     <div class="column">
//         <h5>Дополнительные сведения</h5>
//         <label>Тип топлива<br> [text FUEL]</label>
//         <label>Тип коробки передач<br> [text TRANSMISSION]</label>
//         <label>Тип привода<br> [text DRIVE]</label>
//         <label>Тип кузова<br> [text CABIN]</label>
//         <label>Кол-во дверей<br> [text DOORS]</label>
//         <label>[checkbox RTL "Правый руль"]</label>
//         <label>[checkbox CONDITIONER "Кондиционер"]</label>
//     </div>

//     <label>Дополнительная информация об автомобиле<br> [textarea ADVANCED x3]</label>

//     <div class="buttons"></div>
// </div>

// <div class="step" data-step="2">
//     <h5>Запрашиваемые товары</h5>
//     <div class="column">
//         <label>Опишите детали в вольной форме (Код, название, количество)<br> [textarea DESCRIPTION x4]</label>
//     </div>

//     <div class="buttons"></div>
// </div>

// <div class="step" data-step="3">
//     <h5>Информация о пользователе</h5>
//     <div class="column">
//         <label>E-mail*<br> [email* USER_EMAIL]</label>
//         <label>Имя*<br> [text* USER_NAME]</label>
//         <label>Телефон*<br> [text* USER_PHONE]</label>
//         <label>Город<br> [text USER_CITY]</label>
//         <label>Дополнительная информация<br> [textarea USER_ADVANCED x3]</label>
//     </div>

//     <label>[acceptance PRIVACY] * Я соглас(на)ен на обработку персональных данных в соответствии с законодательством России и Политикой конфиденциальности. [/acceptance]</label>

//     <div class="buttons">[submit class:btn class:btn-primary "Отправить"]</div>
// </div>