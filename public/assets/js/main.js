/** 28
 * Select Image
 * Button Quantity
 * Delete File
 * Go Top
 * Variant Picker
 * Change Value
 * Sidebar Mobile
 * Header Sticky
 * Total Price Variant
 * Handle Footer
 * Add Wishlist
 * Handle Sidebar Filter
 * Estimate Shipping
 * Coupon Copy
 * Parallaxie
 * Click Active
 * Handle Mobile Menu
 * Write Review
 * Show Password
 * Select Category
 * Hover Pin
 * Rate Click
 * Quickview
 * Video Popup
 * Indicator Item
 * Only One Active
 * Write Review
 * Click Scroll
 * Preloader
 */

(function ($) {
    "use strict";

    /* Select Image
    -------------------------------------------------------------------------*/
    var dropdownSelect = function () {
        if ($(".tf-dropdown-select").length > 0) {
            const selectIMG = $(".tf-dropdown-select");

            selectIMG.find("option").each((idx, elem) => {
                const selectOption = $(elem);
                const imgURL = selectOption.attr("data-thumbnail");
                if (imgURL) {
                    selectOption.attr("data-content", `<img src="${imgURL}" alt="Country" /> ${selectOption.text()}`);
                }
            });
            selectIMG.selectpicker();
        }
    };

    /* Button Quantity
    -------------------------------------------------------------------------*/
    var btnQuantity = function () {
        $(".minus-btn").on("click", function (e) {
            e.preventDefault();
            var $this = $(this);
            var $input = $this.closest("div").find("input");
            var value = parseInt($input.val(), 10);

            if (value > 1) {
                value = value - 1;
            }
            $input.val(value);
        });

        $(".plus-btn").on("click", function (e) {
            e.preventDefault();
            var $this = $(this);
            var $input = $this.closest("div").find("input");
            var value = parseInt($input.val(), 10);

            if (value > -1) {
                value = value + 1;
            }
            $input.val(value);
        });
    };

    /* Delete File 
    -------------------------------------------------------------------------*/
    var deleteFile = function (e) {
        function updateCount() {
            var count = $(".list-file-delete .file-delete").length;
            $(".prd-count").text(count);
        }

        function updateTotalPrice() {
            var total = 0;

            $(".list-file-delete .mini-product-cart").each(function () {
                var priceText = $(this).find(".tf-mini-card-price").text().replace("$", "").replace(",", "").trim();
                var price = parseFloat(priceText);
                if (!isNaN(price)) {
                    total += price;
                }
            });

            var formatted = total.toLocaleString("en-US", { style: "currency", currency: "USD" });
            $(".tf-totals-total-value").text(formatted);
        }

        function updatePriceEach() {
            $(".each-prd").each(function () {
                var priceText = $(this).find(".each-price").text().replace("$", "").replace(",", "").trim();
                var price = parseFloat(priceText);
                var quantity = parseInt($(this).find(".quantity-product").val(), 10);
                if (!isNaN(price) && !isNaN(quantity)) {
                    var subtotal = price * quantity;
                    var formatted = subtotal.toLocaleString("en-US", { style: "currency", currency: "USD" });
                    $(this).find(".each-subtotal-price").text(formatted);
                }
            });
        }

        function updateTotalPriceEach() {
            var total = 0;

            $(".each-list-prd .each-prd").each(function () {
                var priceText = $(this).find(".each-subtotal-price").text().replace("$", "").replace(",", "").trim();
                var price = parseFloat(priceText);
                var quantity = parseInt($(this).find(".quantity-product").val(), 10);

                if (!isNaN(price) && !isNaN(quantity)) {
                    total += price * quantity;
                }
            });

            var formatted = total.toLocaleString("en-US", { style: "currency", currency: "USD" });
            $(".each-total-price").text(formatted);
        }

        function checkListEmpty() {
            $(".wrap-empty_text").each(function () {
                var $listEmpty = $(this);
                var $textEmpty = $listEmpty.find(".box-text_empty");
                var $otherChildren = $listEmpty.find(".list-empty").children().not(".box-text_empty");
                var $boxEmpty = $listEmpty.find(".box-empty_clear");
                if ($otherChildren.length > 0) {
                    $textEmpty.hide();
                } else {
                    $textEmpty.show();
                    $boxEmpty.hide();
                }
            });
        }

        if ($(".main-list-clear").length) {
            $(".main-list-clear").each(function () {
                var $mainList = $(this);

                $mainList.find(".clear-list-empty").on("click", function () {
                    $mainList.find(".list-empty").children().not(".box-text_empty").remove();
                    checkListEmpty();
                });
            });
        }
        function ortherDel() {
            $(".container .orther-del").remove();
        }
        $(".list-file-delete").on("input", ".quantity-product", function () {
            updateTotalPrice();
        });

        $(".list-file-delete,.each-prd").on("click", ".minus-quantity, .plus-quantity", function () {
            var $quantityInput = $(this).siblings(".quantity-product");
            var currentQuantity = parseInt($quantityInput.val(), 10);

            if ($(this).hasClass("plus-quantity")) {
                $quantityInput.val(currentQuantity + 1);
            } else if ($(this).hasClass("minus-quantity") && currentQuantity > 1) {
                $quantityInput.val(currentQuantity - 1);
            }

            updateTotalPrice();
            updatePriceEach();
            updateTotalPriceEach();
        });

        $(".remove").on("click", function (e) {
            e.preventDefault();
            var $this = $(this);
            $this.closest(".file-delete").remove();
            updateCount();
            updateTotalPrice();
            checkListEmpty();
            updateTotalPriceEach();
            ortherDel();
        });

        $(".clear-file-delete").on("click", function (e) {
            e.preventDefault();
            $(this).closest(".list-file-delete").find(".file-delete").remove();
            updateCount();
            updateTotalPrice();
            checkListEmpty();
        });
        checkListEmpty();
        updateCount();
        updateTotalPrice();
        updatePriceEach();
        updateTotalPriceEach();
    };

    /* Go Top
    -------------------------------------------------------------------------*/
    var goTop = function () {
        var $goTop = $("#goTop");
        var $borderProgress = $(".border-progress");

        $(window).on("scroll", function () {
            var scrollTop = $(window).scrollTop();
            var docHeight = $(document).height() - $(window).height();
            var scrollPercent = (scrollTop / docHeight) * 100;
            var progressAngle = (scrollPercent / 100) * 360;

            $borderProgress.css("--progress-angle", progressAngle + "deg");

            if (scrollTop > 100) {
                $goTop.addClass("show");
            } else {
                $goTop.removeClass("show");
            }
        });

        $goTop.on("click", function () {
            $("html, body").animate({ scrollTop: 0 }, 0);
        });
    };

    /* Variant Picker
    -------------------------------------------------------------------------*/
    var variantPicker = function () {
        if ($(".variant-picker-item").length) {
            $(".unit-btn").on("click", function (e) {
                var value = $(this).data("unit");
                $(".value-currentUnit").text(value);

                $(this).closest(".variant-picker-values").find(".unit-btn").removeClass("active");
                $(this).addClass("active");
            });
        }
    };

    /* Change Value
    -------------------------------------------------------------------------*/
    var changeValue = function () {
        if ($(".tf-dropdown-sort").length > 0) {
            $(".select-item").on("click", function (event) {
                $(this).closest(".tf-dropdown-sort").find(".text-sort-value").text($(this).find(".text-value-item").text());

                $(this).closest(".dropdown-menu").find(".select-item.active").removeClass("active");

                $(this).addClass("active");

                var color = $(this).data("value-color");
                $(this).closest(".tf-dropdown-sort").find(".btn-select").find(".current-color").css("background", color);
            });
        }
    };

    /* Sidebar Mobile
    -------------------------------------------------------------------------*/
    var sidebarMobile = function () {
        if ($(".sidebar-content-wrap").length > 0) {
            var sidebar = $(".sidebar-content-wrap").html();
            $(".sidebar-mobile-append").append(sidebar);
        }
    };

    /* Header Sticky
  -------------------------------------------------------------------------*/
    var headerSticky = function () {
        let lastScrollTop = 0;
        let delta = 5;
        let navbarHeight = $("header").outerHeight();
        let didScroll = false;

        $(window).scroll(function () {
            didScroll = true;
        });

        setInterval(function () {
            if (didScroll) {
                let st = $(window).scrollTop();
                navbarHeight = $("header").outerHeight();

                if (st > navbarHeight) {
                    if (st > lastScrollTop + delta) {
                        $("header").css("top", `-${navbarHeight}px`);
                        $(".sticky-top").css("top", "15px");
                    } else if (st < lastScrollTop - delta) {
                        if ($("header").hasClass("header-abs")) {
                            $("header").css("top", "15px");
                        } else {
                            $("header").css("top", "0");
                        }
                        $("header").addClass("header-sticky");
                        $(".sticky-top").css("top", `${30 + navbarHeight}px`);
                    }
                } else {
                    $("header").css("top", "unset");
                    $("header").removeClass("header-sticky");
                    $(".sticky-top").css("top", "15px");
                }

                lastScrollTop = st;
                didScroll = false;
            }
        }, 250);
    };

    /* Total Price Variant
    -------------------------------------------------------------------------*/
    var totalPriceVariant = function () {
        $(".tf-product-info-wrap").each(function () {
            var productItem = $(this);
            var priceEl = productItem.find(".price-on-sale");
            var quantityInput = productItem.find(".quantity-product");
            if (!priceEl.data("price")) {
                var initialPrice = parseFloat(priceEl.text().replace("$", "").replace(/,/g, ""));
                priceEl.data("price", initialPrice);
            }
            productItem.find(".unit-btn").on("click", function () {
                var rawPrice = $(this).attr("data-price");
                var newPrice = parseFloat(rawPrice.replace(/,/g, "")) || basePrice;
                quantityInput.val(1);
                productItem.find(".price-on-sale")
                    .text(`$${newPrice.toLocaleString("en-US", { minimumFractionDigits: 2 })}`)
                    .data("price", newPrice);
                updateTotalPrice(newPrice, productItem);
            });

            productItem.find(".btn-increase").on("click", function () {
                var currentQuantity = parseInt(quantityInput.val(), 10);
                quantityInput.val(currentQuantity + 1);
                updateTotalPrice(null, productItem);
            });

            productItem.find(".btn-decrease").on("click", function () {
                var currentQuantity = parseInt(quantityInput.val(), 10);
                if (currentQuantity > 1) {
                    quantityInput.val(currentQuantity - 1);
                    updateTotalPrice(null, productItem);
                }
            });

            function updateTotalPrice(price, scope) {
                var currentPrice = price || parseFloat(scope.find(".price-on-sale").data("price"));
                var quantity = parseInt(scope.find(".quantity-product").val(), 10);
                var totalPrice = currentPrice * quantity;
                scope.find(".price-add").text(`$${totalPrice.toLocaleString("en-US", { minimumFractionDigits: 2 })}`);
            }
            updateTotalPrice(null, productItem);
        });
    };

    /* Handle Footer
    -------------------------------------------------------------------------*/
    var handleFooter = function () {
        var footerAccordion = function () {
            var args = { duration: 250 };
            $(".footer-heading-mobile").on("click", function () {
                var $parent = $(this).parent(".footer-col-block");
                var $content = $(this).next();

                $parent.toggleClass("open");

                if (!$parent.hasClass("open")) {
                    $content.slideUp(args);
                } else {
                    $content.slideDown(args);
                }
            });
        };

        function handleAccordion() {
            if (window.matchMedia("only screen and (max-width: 575px)").matches) {
                if (!$(".footer-heading-mobile").data("accordion-initialized")) {
                    footerAccordion();
                    $(".footer-heading-mobile").data("accordion-initialized", true);
                }
            } else {
                $(".footer-heading-mobile")
                    .off("click")
                    .removeData("accordion-initialized")
                    .each(function () {
                        $(this).parent(".footer-col-block").removeClass("open").end().next().removeAttr("style");
                    });
            }
        }

        handleAccordion();
        $(window).on("resize", handleAccordion);
    };

    /* Add Wishlist
    -------------------------------------------------------------------------*/
    var addWishList = function () {
        $(".btn-add-wishlist, .card-product .wishlist").on("click", function (e) {
            e.preventDefault();
            let $this = $(this);
            let icon = $this.find(".icon");
            let tooltip = $this.find(".tooltip");

            $this.toggleClass("addwishlist");

            if ($this.hasClass("addwishlist")) {
                icon.removeClass("icon-heart-stroke").addClass("icon-heart");
                tooltip.text("Remove Wishlist");
            } else {
                icon.removeClass("icon-heart").addClass("icon-heart-stroke");
                tooltip.text("Add to Wishlist");
            }
        });
    };

    /* Handle Sidebar Filter 
    -------------------------------------------------------------------------*/
    var handleSidebarFilter = function () {
        $("#filterShop,.sidebar-btn").on("click", function () {
            if ($(window).width() <= 1200) {
                $(".sidebar-filter,.overlay-filter").addClass("show");
            }
        });
        $(".close-filter,.overlay-filter").on("click", function () {
            $(".sidebar-filter,.overlay-filter").removeClass("show");
        });
    };

    /* Estimate Shipping
    -------------------------------------------------------------------------*/
    var estimateShipping = function () {
        if ($(".estimate-shipping").length) {
            const $countrySelect = $("#shipping-country-form");
            const $provinceSelect = $("#shipping-province-form");
            const $zipcodeInput = $("#zipcode");
            const $zipcodeMessage = $("#zipcode-message");
            const $zipcodeSuccess = $("#zipcode-success");
            const $shippingForm = $("#shipping-form");

            function updateProvinces() {
                const selectedCountry = $countrySelect.val();
                const $selectedOption = $countrySelect.find("option:selected");
                const provincesData = $selectedOption.attr("data-provinces");

                const provinces = JSON.parse(provincesData);
                $provinceSelect.empty();

                if (provinces.length === 0) {
                    $provinceSelect.append($("<option>").text("------"));
                } else {
                    provinces.forEach(function (province) {
                        $provinceSelect.append($("<option>").val(province[0]).text(province[1]));
                    });
                }
            }

            $countrySelect.on("change", updateProvinces);

            function validateZipcode(zipcode, country) {
                let regex;

                switch (country) {
                    case "Australia":
                    case "Austria":
                    case "Belgium":
                    case "Denmark":
                        regex = /^\d{4}$/;
                        break;
                    case "Canada":
                        regex = /^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/;
                        break;
                    case "Czech Republic":
                    case "Finland":
                    case "France":
                    case "Germany":
                    case "Mexico":
                    case "South Korea":
                    case "Spain":
                    case "Italy":
                        regex = /^\d{5}$/;
                        break;
                    case "United States":
                        regex = /^\d{5}(-\d{4})?$/;
                        break;
                    case "United Kingdom":
                        regex = /^[A-Za-z]{1,2}\d[A-Za-z\d]? \d[A-Za-z]{2}$/;
                        break;
                    case "India":
                    case "Vietnam":
                        regex = /^\d{6}$/;
                        break;
                    case "Japan":
                        regex = /^\d{3}-\d{4}$/;
                        break;
                    default:
                        return true;
                }

                return regex.test(zipcode);
            }

            $shippingForm.on("submit", function (event) {
                const zipcode = $zipcodeInput.val().trim();
                const country = $countrySelect.val();

                if (!validateZipcode(zipcode, country)) {
                    $zipcodeMessage.show();
                    $zipcodeSuccess.hide();
                    event.preventDefault();
                } else {
                    $zipcodeMessage.hide();
                    $zipcodeSuccess.show();
                    event.preventDefault();
                }
            });

            $(window).on("load", updateProvinces);
        }
    };

    /* Coupon Copy
    -------------------------------------------------------------------------*/
    var textCopy = function () {
        $(".coupon-copy-wrap,.btn-coppy-text").on("click", function () {
            const couponCode = $(this).find(".coupon-code,.coppyText").text().trim();

            if (navigator.clipboard) {
                navigator.clipboard
                    .writeText(couponCode)
                    .then(function () {
                        alert("Copied! " + couponCode);
                    })
                    .catch(function (err) {
                        alert("Unable to copy: " + err);
                    });
            } else {
                const tempInput = $("<input>");
                $("body").append(tempInput);
                tempInput.val(couponCode).select();
                document.execCommand("copy");
                tempInput.remove();
                alert("Copied! " + couponCode);
            }
        });
    };

    /* Parallaxie 
    -------------------------------------------------------------------------*/
    var parallaxie = function () {
        var $window = $(window);

        if ($(".parallaxie").length) {
            function initParallax() {
                if ($(".parallaxie").length && $window.width() > 991) {
                    $(".parallaxie").parallaxie({
                        speed: 0.55,
                        offset: 0,
                    });
                }
            }

            initParallax();

            $window.on("resize", function () {
                if ($window.width() > 991) {
                    initParallax();
                }
            });
        }
    };

    /* Click Active 
    -------------------------------------------------------------------------*/
    var clickActive = function () {
        function isAllowed($container) {
            return !$container.hasClass("active-1600") || window.innerWidth < 1600;
        }

        let previousWidth = window.innerWidth;

        $(window).on("resize", function () {
            const currentWidth = window.innerWidth;

            const crossedBreakpoint = (previousWidth < 1600 && currentWidth >= 1600) || (previousWidth >= 1600 && currentWidth < 1600);

            if (crossedBreakpoint) {
                $(".main-action-active").each(function () {
                    $(this).find(".btn-active, .active-item").removeClass("active");
                });

                if (previousWidth < 1600 && currentWidth >= 1600) {
                    $(".main-action-active.active-1600").each(function () {
                        const $container = $(this);
                        const $btn = $container.find(".btn-active");
                        const $item = $container.find(".active-item");

                        $btn.addClass("active");
                        $item.addClass("active");
                    });
                }
            }

            previousWidth = currentWidth;
        });

        $(".btn-active").on("click", function (event) {
            const $container = $(this).closest(".main-action-active");

            if (!isAllowed($container)) return;

            event.stopPropagation();

            const $activeItem = $container.find(".active-item");
            const isResponsive = $container.hasClass("active-1600") && window.innerWidth < 1600;

            if (isResponsive) {
                $(".main-action-active").each(function () {
                    if (this !== $container[0]) {
                        $(this).find(".btn-active, .active-item").removeClass("active");
                    }
                });
            } else {
                $(".main-action-active").each(function () {
                    const $other = $(this);
                    if (this !== $container[0] && (!$other.hasClass("active-1600") || window.innerWidth < 1600)) {
                        $other.find(".btn-active, .active-item").removeClass("active");
                    }
                });
            }

            $(this).toggleClass("active");
            $activeItem.toggleClass("active");
        });

        $(document).on("click", function (event) {
            const isMobile = window.innerWidth < 1600;

            $(".main-action-active").each(function () {
                const $container = $(this);
                const is1600 = $container.hasClass("active-1600");

                if ((is1600 && isMobile) || !is1600) {
                    if (!$(event.target).closest($container).length) {
                        $container.find(".btn-active, .active-item").removeClass("active");
                    }
                }
            });
        });

        $(".choose-option-item").on("click", function () {
            const $container = $(this).closest(".main-action-active");
            if (!isAllowed($container)) return;

            $(this).closest(".choose-option-list").find(".select-option").removeClass("select-option");
            $(this).addClass("select-option");
        });
    };

    /* Handle Mobile Menu
    -------------------------------------------------------------------------*/
    var handleMobileMenu = function () {
        const $desktopMenu = $(".box-nav-menu:not(.not-append)").clone();
        $desktopMenu.find(".list-ver, .list-hor,.mn-none").remove();
        const $mobileMenu = $('<ul class="nav-ul-mb"></ul>');
        const $iconArrow = "ic-custom";

        $desktopMenu.find("> li.menu-item").each(function (i, menuItem) {
            const $item = $(menuItem);
            const text = $item.find("> a.item-link .text").clone().children().remove().end().text().trim();
            const submenu = $item.find("> .sub-menu");
            const id = "dropdown-menu-" + i;

            if (submenu.length > 0) {
                const $li = $(`
                <li class="nav-mb-item">
                    <a href="#${id}" class="collapsed mb-menu-link" data-bs-toggle="collapse" aria-expanded="false" aria-controls="${id}">
                        <span>${text}</span>
                        <span class="icon ${$iconArrow}"></span>
                    </a>
                    <div id="${id}" class="collapse"></div>
                </li>
            `);
                const $subNav = $('<ul class="sub-nav-menu"></ul>');
                submenu.find("> .sub-menu_list > li").each(function (j) {
                    const $liSub = $(this);

                    if ($liSub.hasClass("has-menu_lv2")) {
                        const heading = $liSub.find("> a.sub-menu_link .text").text().trim();
                        const subId = `${id}-group-${j}`;
                        const $group = $(`
                        <li>
                            <a href="#${subId}" class="collapsed sub-nav-link" data-bs-toggle="collapse" aria-expanded="false" aria-controls="${subId}">
                                <span>${heading}</span>
                                <span class="icon ${$iconArrow}"></span>
                            </a>
                            <div id="${subId}" class="collapse">
                                <ul class="sub-nav-menu sub-menu-level-2"></ul>
                            </div>
                        </li>
                    `);
                        $liSub.find("> .sub-menu-lv2 > li > a.sub-menu_link").each(function () {
                            const $link = $(this);
                            const href = $link.attr("href") || "#";
                            const titleHtml = $link.clone().children().remove().end().html().trim();
                            const fullHtml = $link.html();

                            if (titleHtml !== "" || fullHtml !== "") {
                                $group.find(".sub-menu-level-2")
                                    .append(`<li><a href="${href}" class="sub-nav-link">${fullHtml}</a></li>`);
                            }
                        });

                        $subNav.append($group);
                    } else {
                        const $link = $liSub.find("> a.sub-menu_link");
                        const href = $link.attr("href") || "#";
                        const titleHtml = $link.html();
                        if (titleHtml !== "") {
                            $subNav.append(`<li><a href="${href}" class="sub-nav-link">${titleHtml}</a></li>`);
                        }
                    }
                });
                if ($subNav.children().length === 0) {
                    submenu.find("a").each(function () {
                        const link = $(this).attr("href") || "#";
                        const titleHtml = $(this).html();
                        if (titleHtml !== "") {
                            $subNav.append(`<li><a href="${link}" class="sub-nav-link">${titleHtml}</a></li>`);
                        }
                    });
                }
                $li.find(`#${id}`).append($subNav);
                $mobileMenu.append($li);
            } else {
                $mobileMenu.append(
                    `<li class="nav-mb-item"><a href="${$item.find("a").attr("href")}" class="mb-menu-link"><span>${text}</span></a></li>`
                );
            }
        });

        $("#wrapper-menu-navigation").empty().append($mobileMenu.html());
    };

    /* Write Review
    ------------------------------------------------------------------------------------- */
    var writeReview = function () {
        if ($(".write-cancel-review-wrap").length > 0) {
            $(".btn-comment-review").click(function () {
                $(this).closest(".write-cancel-review-wrap").toggleClass("write-review");
            });
        }
    };

    /* Show Password 
    -------------------------------------------------------------------------*/
    var showPassword = function () {
        $(".toggle-pass").on("click", function () {
            const wrapper = $(this).closest(".password-wrapper");
            const input = wrapper.find(".password-field");
            const icon = $(this);

            if (input.attr("type") === "password") {
                input.attr("type", "text");
                icon.removeClass("icon-eye-open").addClass("icon-eye-slash");
            } else {
                input.attr("type", "password");
                icon.removeClass("icon-eye-slash").addClass("icon-eye-open");
            }
        });
    };

    /* Select Category
    -------------------------------------------------------------------------*/
    var customSelectCate = function () {
        $("select#product_cat").each(function () {
            var $this = $(this),
                selectOptions = $(this).children("option").length;
            $this.addClass("hide-select");
            $this.after('<div class="tf-select-custom"></div>');
            var $customSelect = $this.next("div.tf-select-custom");
            $customSelect.text($this.children("option").eq(0).text());
            var $optionlist = $(
                '<ul class="select-options" /><div class="header-select-option"><span>Select Categories</span><span class="close-option"><i class="icon-close"></i></div>'
            ).insertAfter($customSelect);
            for (var i = 0; i < selectOptions; i++) {
                $("<li />", {
                    text: $this.children("option").eq(i).text(),
                    rel: $this.children("option").eq(i).val(),
                }).appendTo($optionlist);
            }
            var $optionlistItems = $optionlist.children("li");
            $customSelect.click(function (e) {
                e.stopPropagation();
                $("div.tf-select-custom.active")
                    .not(this)
                    .each(function () {
                        $(this).removeClass("active").next("ul.select-options").hide();
                    });
                $(this).toggleClass("active").next("ul.select-options").slideToggle();
            });
            $optionlistItems.click(function (e) {
                e.stopPropagation();
                $customSelect.text($(this).text()).removeClass("active");
                $this.val($(this).attr("rel"));
                $optionlist.hide();
            });
            $(document).click(function () {
                $customSelect.removeClass("active");
                $optionlist.hide();
            });
            $(".close-option").click(function () {
                $customSelect.removeClass("active");
                $optionlist.hide();
            });
        });
    };

    /* Hover Pin
  -------------------------------------------------------------------------*/
    var hoverPin = function () {
        $(".tf-lookbook-hover").each(function () {
            const $container = $(this);

            $container.find(".bundle-pin-item").on("mouseover", function () {
                const $hoverWrap = $container.find(".bundle-hover-wrap");
                $hoverWrap.addClass("has-hover");

                const $el = $container.find("." + this.id).show();
                $hoverWrap.find(".bundle-hover-item").not($el).addClass("no-hover");
            });

            $container.find(".bundle-pin-item").on("mouseleave", function () {
                const $hoverWrap = $container.find(".bundle-hover-wrap");
                $hoverWrap.removeClass("has-hover");
                $hoverWrap.find(".bundle-hover-item").removeClass("no-hover");
            });
        });
    };

    /* Btn Active
    -------------------------------------------------------------------------*/
    var btnActive = () => {
        $(".btn-add-to-card").on("click", function (e) {
            let $this = $(this);
            $this.addClass("active");
            $this.closest(".card-product").on("mouseleave", () => {
                $this.removeClass("active");
            })
        });
    }

    /* Rate Click
    -------------------------------------------------------------------------*/
    var rateClick = () => {
        const stars = document.querySelectorAll(".rate-click li");
        let selectedRating = 0;

        stars.forEach((star, idx) => {
            star.addEventListener("mouseenter", () => {
                resetHover();
                for (let i = 0; i <= idx; i++) {
                    stars[i].classList.add("hover");
                }
            });

            star.addEventListener("mouseleave", () => {
                resetHover();
            });
            star.addEventListener("click", () => {
                selectedRating = idx + 1;
                resetActive();
                for (let i = 0; i < selectedRating; i++) {
                    stars[i].classList.add("active");
                }
            });
        });

        function resetHover() {
            stars.forEach(s => s.classList.remove("hover"));
        }

        function resetActive() {
            stars.forEach(s => s.classList.remove("active"));
        }
    }

    /* Quickview
    -------------------------------------------------------------------------*/
    var quickViewDetail = () => {
        $(".canvas-quickview").each(function () {
            const $mainCanvas = $(".tf-quick-view");

            const $btnMinus = $mainCanvas.find(".minus-prd");
            const $btnPlus = $mainCanvas.find(".plus-prd");
            const $quantityInput = $mainCanvas.find(".quantity-product");
            const $priceOnSale = $mainCanvas.find(".price-on-sale");
            const $priceAdd = $mainCanvas.find(".price-add");

            function updateTotal() {
                let qty = parseInt($quantityInput.val()) || 1;
                if (qty < 1) qty = 1;

                const unitPrice = parseFloat(
                    $priceOnSale.text().replace(/[^0-9.]/g, "")
                );

                const total = (unitPrice * qty).toFixed(2);

                $quantityInput.val(qty);
                $priceAdd.text(`$${total}`);
            }

            $btnMinus.on("click", function (e) {
                e.preventDefault();
                let qty = parseInt($quantityInput.val()) || 1;
                qty = Math.max(1, qty - 1);
                $quantityInput.val(qty);
                updateTotal();
            });

            $btnPlus.on("click", function (e) {
                e.preventDefault();
                let qty = parseInt($quantityInput.val()) || 1;
                $quantityInput.val(qty + 1);
                updateTotal();
            });

            $quantityInput.on("input", function () {
                updateTotal();
            });
        });
    }

    /* Video Popup
    -------------------------------------------------------------------------*/
    var videoPopup = function () {
        if ($("div").hasClass("video-wrap")) {
            $(".popup-youtube").magnificPopup({
                type: "iframe",
            });
        }
    };

    /* Indicator Item
-------------------------------------------------------------------------*/
    var indicatorItem = () => {
        const wrappers = document.querySelectorAll(".indicator-main");

        wrappers.forEach((wrapper) => {
            const indicator = document.createElement("div");
            indicator.classList.add("indicator");
            wrapper.appendChild(indicator);

            const items = wrapper.querySelectorAll(".indicator-item");

            const moveIndicator = (el) => {
                const { offsetLeft, offsetWidth } = el;
                indicator.style.left = offsetLeft + "px";
                indicator.style.width = offsetWidth + "px";
                indicator.style.opacity = 1;
            };

            items.forEach((item) => {
                item.addEventListener("mouseenter", () => {
                });

                item.addEventListener("click", () => {
                    wrapper.querySelectorAll(".indicator-item.current").forEach((el) => el.classList.remove("current"));

                    item.classList.add("current");
                    moveIndicator(item);
                });
            });

            wrapper.addEventListener("mouseleave", () => {
                const current = wrapper.querySelector(".indicator-item.current");
                if (current) {
                    moveIndicator(current);
                } else {
                    indicator.style.opacity = 0;
                }
            });

            const current = wrapper.querySelector(".indicator-item.current");
            if (current) {
                moveIndicator(current);
            }
        });
    };

    /* Only One Active
    -------------------------------------------------------------------------*/
    var privacy = () => {
        document.addEventListener("scroll", function () {
            const links = document.querySelectorAll('.list-name-privacy a[href^="#"]');

            links.forEach(link => {
                const id = link.getAttribute("href");

                if (!id || id === "#") return;

                const section = document.querySelector(id);
                if (!section) return;

                const rect = section.getBoundingClientRect();
                const triggerPoint = window.innerHeight * 0.3;

                const inView = rect.top <= triggerPoint && rect.bottom > triggerPoint;

                if (inView) {
                    links.forEach(l => l.classList.remove("active"));
                    link.classList.add("active");
                }
            });
        });
    }

    /* Write Review
    -------------------------------------------------------------------------*/
    var writeReview = function () {
        $(".write-cancel-review-wrap").on("click", ".btn-comment-review", function () {
            $(this)
                .closest(".write-cancel-review-wrap")
                .toggleClass("write-review");
        });
    };

    /* Click Scroll
    -------------------------------------------------------------------------*/
    var clickScroll = () => {
        $(document).on("click", ".click-scroll", function (e) {
            e.preventDefault();

            var targetId = $(this).attr("href");
            var target = $(targetId);

            if (target.length) {
                var headerHeight = $("header").outerHeight() || 0;
                var offsetTop = target.offset().top - headerHeight - 15;

                $("html, body").animate({
                    scrollTop: offsetTop
                }, 100);
            }
        });
    }

    /* Preloader
    -------------------------------------------------------------------------*/
    var preloader = function () {
        setTimeout(function () {
            $("#preload").fadeOut(300, function () {
                $(this).remove();
            });
        }, 300);
    };
    // Dom Ready
    $(function () {
        headerSticky();
        dropdownSelect();
        btnQuantity();
        deleteFile();
        variantPicker();
        changeValue();
        sidebarMobile();
        totalPriceVariant();
        handleFooter();
        addWishList();
        handleSidebarFilter();
        estimateShipping();
        textCopy();
        parallaxie();
        clickActive();
        handleMobileMenu();
        showPassword();
        customSelectCate();
        hoverPin();
        btnActive();
        rateClick();
        quickViewDetail();
        videoPopup();
        indicatorItem();
        privacy();
        goTop();
        writeReview();
        clickScroll();

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", preloader);
        } else {
            preloader();
        }
    });
})(jQuery);
