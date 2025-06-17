<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Gateway Testing SPA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-clifford: #da373d;
        }

        .animated-bg {
            background: linear-gradient(270deg, #1a1a2e, #0f3460, #1472ff, #e94560, #16213e);
            background-size: 200% 200%;
            animation: gradientMove 15s ease infinite;
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%;}
            50% { background-position: 100% 50%;}
            100% { background-position: 0% 50%;}
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.20; }
            50% { opacity: 0.40; }
        }
        .animate-pulse-slow { animation: pulse-slow 6s ease-in-out infinite; }

        body, html, * {
            cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><circle cx="12" cy="12" r="7" stroke="white" stroke-width="2.2" fill="none"/><line x1="12" y1="2" x2="12" y2="22" stroke="white" stroke-width="1.5"/><line x1="2" y1="12" x2="22" y2="12" stroke="white" stroke-width="1.5"/><circle cx="12" cy="12" r="2.3" fill="white"/></svg>') 12 12, default;
        }
        button, [type="button"], [role="button"], a, label, input[type="radio"], input[type="checkbox"] {
            cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"><circle cx="16" cy="16" r="8" stroke="white" stroke-width="3" fill="none"/><line x1="16" y1="2" x2="16" y2="30" stroke="white" stroke-width="2"/><line x1="2" y1="16" x2="30" y2="16" stroke="white" stroke-width="2"/><circle cx="16" cy="16" r="3" fill="white"/></svg>') 16 16, pointer;
        }

        /* Force Adyen sr-only panel to be hidden visually */
        .adyen-checkout-sr-panel--sr-only {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0,0,0,0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
    </style>

    <script>
        // Each payable in $store.payables has:
        // {
        //   id, name, description, currency, amount_minor, location, type, feature, created_at, updated_at
        // }
        document.addEventListener('alpine:init', () => {
            Alpine.store('context', {
                id: 'e-cola-inc',
                apiKey: 'lsdsupermix'
            });
            Alpine.store('payables_raw', @json($payables));
            Alpine.store('payables', Alpine.store('payables_raw').map(function(p) {
                return {
                    id: p.id ?? null,
                    name: p.data?.name ?? null,
                    description: p.data?.description ?? null,
                    currency: p.data?.currency ?? null,
                    amount_minor: p.data?.amount_minor ?? null,
                    location: p.data?.location ?? null,
                    type: p.data?.type ?? null,
                    feature: p.data?.feature ?? null,
                    created_at: p.created_at ?? null,
                    updated_at: p.updated_at ?? null,
                };
            }));

            Alpine.store('paymentDemo', {
                // Interval/timer for refund status auto-refresh
                refundStatusRefreshInterval: null,
                refundStatusesRefreshing: false,
                async refreshRefundStatuses() {
                    console.log('[refreshRefundStatuses] called');
                    // Only one refresh at a time
                    if (this.refundStatusesRefreshing) {
                        console.log('[refreshRefundStatuses] Already refreshing, aborting.');
                        return;
                    }
                    console.log('[refreshRefundStatuses] Started');
                    this.refundStatusesRefreshing = true;
                    try {
                        let updated = false;
                        const purchased = this.context.purchasedPayables || [];
                        console.log('[refreshRefundStatuses] Checking purchased payables:', purchased.map(p => ({id: p.id, refund_status: p.refund_status, refunded: p.refunded, payment_id: p.payment_id})));
                        // Loop over all payables with refunded===true, refund_status==='PROCESSING', and payment_id
                        for (let i = 0; i < purchased.length; i++) {
                            const item = purchased[i];
                            if (item.refunded === true && item.refund_status === 'PROCESSING' && item.payment_id) {
                                console.log(`[refreshRefundStatuses] Checking payable id=${item.id}, payment_id=${item.payment_id}`);
                                try {
                                    const res = await fetch(`/api/v1/payments/${item.payment_id}`, {
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-Api-Key': Alpine.store('context').apiKey,
                                        }
                                    });
                                    console.log(`[refreshRefundStatuses] Fetched /api/v1/payments/${item.payment_id}, status=${res.status}`);
                                    if (res.ok) {
                                        const data = await res.json();
                                        console.log(`[refreshRefundStatuses] Response for payment_id=${item.payment_id}:`, data);
                                        // If any refund in data.payment?.refunds has status === 'refunded'
                                        // and refund.payment_id === item.payment_id, set refund_status to 'REFUNDED'
                                        let shouldBeRefunded = false;
                                        let refunds = data.payment?.refunds;
                                        if (!Array.isArray(refunds)) {
                                            refunds = data.refunds; // fallback to root-level refunds array
                                            console.log('[refreshRefundStatuses] Using root-level refunds fallback:', refunds);
                                        }
                                        console.log('[refreshRefundStatuses] refunds array:', refunds, 'item.payment_id:', item.payment_id);
                                        if (Array.isArray(refunds)) {
                                            shouldBeRefunded = refunds.some(r => {
                                                console.log('[refreshRefundStatuses] inspecting refund:', r, 'status:', r.status, 'r.payment_id:', r.payment_id, 'item.payment_id:', item.payment_id);
                                                const refundPid = String(r.payment_id);
                                                const itemPid = String(item.payment_id);
                                                return r && r.status === 'refunded' && refundPid === itemPid;
                                            });
                                            console.log('[refreshRefundStatuses] shouldBeRefunded:', shouldBeRefunded);
                                        }
                                        if (shouldBeRefunded) {
                                            console.log(`[refreshRefundStatuses] Found matching refunded refund for payment_id=${item.payment_id}`);
                                            this.context.purchasedPayables[i] = {
                                                ...item,
                                                refund_status: 'REFUNDED'
                                            };
                                            updated = true;
                                            console.log(`[refreshRefundStatuses] Updated payable id=${item.id} refund_status to REFUNDED`);
                                        } else {
                                            console.log(`[refreshRefundStatuses] No matching refunded refund for payment_id=${item.payment_id}`);
                                            // Optionally, keep as 'PROCESSING' (no change)
                                        }
                                    } else {
                                        console.log(`[refreshRefundStatuses] Fetch not ok for payment_id=${item.payment_id}, status=${res.status}`);
                                    }
                                } catch (e) {
                                    console.log(`[refreshRefundStatuses] Error fetching payment_id=${item.payment_id}:`, e);
                                    // Ignore errors for individual fetches
                                }
                            } else {
                                if (item.refunded !== true)
                                    console.log(`[refreshRefundStatuses] Skipping payable id=${item.id} as refunded !== true`);
                                if (item.refund_status !== 'PROCESSING')
                                    console.log(`[refreshRefundStatuses] Skipping payable id=${item.id} as refund_status !== 'PROCESSING'`);
                                if (!item.payment_id)
                                    console.log(`[refreshRefundStatuses] Skipping payable id=${item.id} as no payment_id`);
                            }
                        }
                        if (updated) {
                            console.log('[refreshRefundStatuses] Updates found, saving context.');
                            this.saveContext();
                        }
                    } finally {
                        this.refundStatusesRefreshing = false;
                        console.log('[refreshRefundStatuses] Finished');
                    }
                },
                resetDemo() {
                    console.log('[resetDemo] called');
                    localStorage.removeItem(this.contextStorageKey);
                    location.reload();
                },
                // Persisted context for purchases
                contextStorageKey: 'context',
                context: {
                    id: 'e-cola-inc',
                    purchasedPayables: [],
                    initialBalance: 100000000,
                    currency: 'USD'
                },
                showWelcomeModal: false,
                showSellModal: false,
                payableToSell: null,
                sellingRefundInFlight: false,
                async sellPayable() {
                    console.log('[sellPayable] called', this.payableToSell);
                    if (!this.payableToSell) {
                        console.log('[sellPayable] No payableToSell, aborting.');
                        return;
                    }
                    if (this.sellingRefundInFlight) {
                        console.log('[sellPayable] Refund already in flight, aborting.');
                        return;
                    }
                    this.sellingRefundInFlight = true;
                    try {
                        console.log('[sellPayable] Initiating refund request for:', this.payableToSell);
                        const res = await fetch('/api/v1/refunds', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Api-Key': Alpine.store('context').apiKey,
                            },
                            body: JSON.stringify({
                                provider: this.payableToSell.provider,
                                payment_id: this.payableToSell.payment_id,
                                amount_minor: this.payableToSell.amount_minor,
                                currency: this.payableToSell.currency,
                            })
                        });
                        console.log('[sellPayable] Refund API response status:', res.status);
                        if (!res.ok) {
                            let err = 'Refund failed';
                            try { const data = await res.json(); err = data.message || err; } catch {}
                            console.log('[sellPayable] Refund failed:', err);
                            alert(err);
                            return;
                        }
                        const data = await res.json();
                        console.log('[sellPayable] Refund API response data:', data);
                        // Mark payable as refunded after successful refund and update refund info
                        this.context.purchasedPayables = this.context.purchasedPayables.map(p =>
                            p.id === this.payableToSell.id
                                ? {
                                    ...p,
                                    refunded: true,
                                    refund_status: 'PROCESSING',
                                    refund_id: data.refund?.id || null,
                                    refund_amount_minor: data.refund?.amount_minor || null,
                                    refund_currency: data.refund?.currency || null
                                }
                                : p
                        );
                        console.log('[sellPayable] Updated purchasedPayables:', this.context.purchasedPayables);
                        this.saveContext();
                        this.payableToSell = null;
                        this.showSellModal = false;
                    } catch (e) {
                        console.log('[sellPayable] Exception during refund:', e);
                        alert(e?.message || 'Refund failed');
                    } finally {
                        this.sellingRefundInFlight = false;
                        console.log('[sellPayable] Refund process finished');
                    }
                },
                loadContext() {
                    console.log('[loadContext] called');
                    try {
                        const data = localStorage.getItem(this.contextStorageKey);
                        console.log('[loadContext] Loaded from localStorage:', data);
                        if (data) {
                            const parsed = JSON.parse(data);
                            if (parsed && typeof parsed === 'object') {
                                this.context = Object.assign(
                                    { id: 'e-cola-inc', purchasedPayables: [], initialBalance: 100000000, currency: 'USD' },
                                    parsed
                                );
                                console.log('[loadContext] Parsed context from storage:', this.context);
                            }
                        }
                        // Ensure initialBalance and currency are set if missing
                        if (typeof this.context.initialBalance !== 'number') {
                            console.log('[loadContext] initialBalance missing or invalid, setting default');
                            this.context.initialBalance = 100000000;
                        }
                        if (!this.context.currency) {
                            console.log('[loadContext] currency missing, setting default');
                            this.context.currency = 'USD';
                        }
                        if ((this.context.purchasedPayables || []).length === 0) {
                            console.log('[loadContext] No purchasedPayables, showing welcome modal');
                            this.showWelcomeModal = true;
                        }
                    } catch (e) {
                        console.log('[loadContext] Error loading context:', e);
                        // Ignore
                    }
                },
                get currentBalance() {
                    const purchased = this.context.purchasedPayables || [];
                    let balance = this.context.initialBalance || 0;
                    for (const item of purchased) {
                        balance -= (item.amount_minor || 0);
                        if (item.refunded === true && item.refund_status === 'REFUNDED') {
                            balance += (item.amount_minor || 0);
                        }
                    }
                    return balance;
                },
                saveContext() {
                    console.log('[saveContext] called', this.context);
                    try {
                        localStorage.setItem(this.contextStorageKey, JSON.stringify(this.context));
                        console.log('[saveContext] Context saved to localStorage');
                    } catch (e) {
                        console.log('[saveContext] Error saving context:', e);
                        // Ignore
                    }
                },
                addPurchasedPayable(payable) {
                    console.log('[addPurchasedPayable] called', payable);
                    if (!payable) {
                        console.log('[addPurchasedPayable] No payable provided, aborting');
                        return;
                    }
                    // Only add if not already purchased
                    if (!this.context.purchasedPayables.some(p => p.id === payable.id)) {
                        const newPayable = {
                            ...payable,
                            provider: (this.selectedProvider || '').toLowerCase(),
                            payment_id: payable.payment_id || (this.initiatePaymentResponse?.payment?.id ?? null),
                        };
                        this.context.purchasedPayables.push(newPayable);
                        console.log('[addPurchasedPayable] Payable added:', newPayable);
                        this.saveContext();
                    } else {
                        console.log('[addPurchasedPayable] Payable already purchased, skipping:', payable.id);
                    }
                },
                themes: [
                    'Dubious Financial Instruments',
                    'Totally Legit Properties',
                    'Get-Rich-Quick Schemes',
                    'Microtransactions',
                    'Nefarious Digital Goods',
                    'Used Getaway Vehicles',
                    'Criminal Opportunities',
                    'Shark Cards',
                    'Modern Art NFTs',
                    'Washed-Up Celebrities'
                ],
                randomTheme: '',
                currentRoute: window.location.hash.replace('#','') || 'main',
                showSelectPaymentProviderModal: false,
                selectedPayable: null,
                selectedProvider: '',
                providers: [
                    {
                        name: 'Adyen',
                        description: 'Trusted by Fortune 500s, money launderers, and your mom.',
                        apiRoute: '/api/v1/payments',
                        async checkout(data) {
                            // Only inject Adyen JS/CSS if not already present
                            let adyenCss = document.getElementById('adyen-css');
                            if (!adyenCss) {
                                adyenCss = document.createElement('link');
                                adyenCss.id = 'adyen-css';
                                adyenCss.rel = 'stylesheet';
                                adyenCss.href = 'https://checkoutshopper-test.adyen.com/checkoutshopper/sdk/5.53.0/adyen.css';
                                document.head.appendChild(adyenCss);
                            }
                            let adyenScript = document.getElementById('adyen-script');
                            let adyenScriptAlreadyLoaded = !!window.AdyenCheckout;
                            if (!adyenScript && !adyenScriptAlreadyLoaded) {
                                adyenScript = document.createElement('script');
                                adyenScript.id = 'adyen-script';
                                adyenScript.src = 'https://checkoutshopper-test.adyen.com/checkoutshopper/sdk/5.53.0/adyen.js';
                                document.body.appendChild(adyenScript);
                            }
                            // Prepare Adyen widget container with custom-styled neon look
                            let container = document.getElementById('checkout-container');
                            if (container) container.innerHTML = '';
                            // Remove any previous dropin container and error message
                            let prevDropin = document.getElementById('adyen-dropin-container');
                            if (prevDropin) prevDropin.remove();
                            let prevError = document.getElementById('adyen-error-message');
                            if (prevError) prevError.remove();
                            // Outer neon container
                            const outerDiv = document.createElement('div');
                            outerDiv.id = 'adyen-dropin-container';
                            outerDiv.style.background = '#191e2c';
                            outerDiv.style.border = '2.5px solid #39FF14';
                            outerDiv.style.borderRadius = '1.1rem';
                            outerDiv.style.padding = '2.1rem 1.3rem 1.8rem 1.3rem';
                            outerDiv.style.margin = '0 auto';
                            outerDiv.style.maxWidth = '460px';
                            outerDiv.style.boxShadow = '0 0 24px 2px #39FF14cc, 0 2px 32px #000a';
                            outerDiv.style.fontFamily = "'Roboto', 'Fira Mono', 'Menlo', monospace";
                            outerDiv.style.position = 'relative';
                            // Adyen widget inner mount point
                            const adyenWidgetDiv = document.createElement('div');
                            adyenWidgetDiv.id = 'adyen-widget-inner';
                            outerDiv.appendChild(adyenWidgetDiv);
                            // Error message area
                            const errorDiv = document.createElement('div');
                            errorDiv.id = 'adyen-error-message';
                            errorDiv.style.color = '#ff3860';
                            errorDiv.style.marginTop = '1.2rem';
                            errorDiv.style.fontWeight = 'bold';
                            errorDiv.style.fontFamily = "'Roboto', monospace";
                            errorDiv.style.fontSize = '1.1rem';
                            outerDiv.appendChild(errorDiv);
                            container.appendChild(outerDiv);

                            // Helper to clean up Adyen widget, script, and CSS
                            const cleanup = () => {
                                // Remove widget DOM
                                if (outerDiv && outerDiv.parentNode) outerDiv.parentNode.removeChild(outerDiv);
                                // Remove Adyen script if present and not used elsewhere
                                let scriptEl = document.getElementById('adyen-script');
                                if (scriptEl) scriptEl.remove();
                                let cssEl = document.getElementById('adyen-css');
                                if (cssEl) cssEl.remove();
                                if (container) container.innerHTML = '';
                            };

                            // Handler for modal close: clean up widget and scripts
                            const modal = document.getElementById('payment-widget-modal');
                            if (modal) {
                                const observer = new MutationObserver(() => {
                                    if (!Alpine.store('paymentDemo').showWidgetModal) {
                                        cleanup();
                                        observer.disconnect();
                                    }
                                });
                                observer.observe(modal, {attributes: true, attributeFilter: ['style', 'class']});
                            }

                            // Function to initialize Adyen Drop-in
                            const initializeAdyen = async () => {
                                const clientKey = data.metadata?.clientKey;
                                const sessionId = data.metadata?.sessionId;
                                const sessionData = data.metadata?.sessionData;
                                const amountMinor = data.payment?.amount_minor;
                                const currency = data.payment?.currency;
                                if (!clientKey || !sessionId || !sessionData || !amountMinor || !currency) {
                                    adyenWidgetDiv.innerHTML = '<div class="text-red-500">Missing Adyen configuration from backend.</div>';
                                    return;
                                }
                                // Adyen Drop-in style config for neon SPA look
                                const neonStyle = {
                                    base: {
                                        color: '#39FF14',
                                        fontFamily: "'Roboto', 'Fira Mono', 'Menlo', monospace",
                                        fontSize: '18px',
                                        background: '#191e2c',
                                        borderRadius: '12px',
                                        border: '2px solid #39FF14',
                                    },
                                    error: {
                                        color: '#ff3860',
                                        border: '2px solid #ff3860',
                                    },
                                    placeholder: {
                                        color: '#6cffb0'
                                    }
                                };
                                const configuration = {
                                    clientKey: clientKey,
                                    environment: 'test',
                                    session: {
                                        id: sessionId,
                                        sessionData: sessionData
                                    },
                                    locale: 'en-US',
                                    countryCode: 'NO',
                                    amount: {
                                        value: amountMinor,
                                        currency: currency
                                    },
                                    // Neon style config for Adyen Drop-in
                                    style: neonStyle,
                                    onPaymentCompleted: function(result, component) {
                                        console.log('Adyen payment complete!', result, Alpine.store('paymentDemo').initiatePaymentResponse);
                                        // Save context purchase before clearing state
                                        Alpine.store('paymentDemo').addPurchasedPayable({
                                            ...Alpine.store('paymentDemo').selectedPayable,
                                            payment_id: Alpine.store('paymentDemo').initiatePaymentResponse?.payment?.id || result?.payment?.id || null
                                        });
                                        Alpine.store('paymentDemo').showWidgetModal = false;
                                        Alpine.store('paymentDemo').widgetInitialized = false;
                                        Alpine.store('paymentDemo').success = '';
                                        Alpine.store('paymentDemo').error = '';
                                        Alpine.store('paymentDemo').initiatePaymentResponse = null;
                                        cleanup();
                                        window.location.hash = 'success';
                                    },
                                    onError: function(error, component) {
                                        // Only show error inline, do not remove widget
                                        errorDiv.textContent = error.message || 'Adyen error';
                                    }
                                };
                                if (window.AdyenCheckout) {
                                    try {
                                        const checkout = await window.AdyenCheckout(configuration);
                                        // Mount with custom container
                                        checkout.create('dropin').mount('#adyen-widget-inner');
                                    } catch (e) {
                                        adyenWidgetDiv.innerHTML = '<div class="text-red-500">Adyen Drop-in failed to initialize.</div>';
                                        errorDiv.textContent = e.message || 'Adyen Drop-in failed to initialize.';
                                        console.error(e);
                                    }
                                } else {
                                    adyenWidgetDiv.innerHTML = '<div class="text-red-500">Adyen Drop-in JS failed to load.</div>';
                                }
                            };
                            // If AdyenCheckout is ready, run immediately; else wait for script load
                            if (window.AdyenCheckout) {
                                initializeAdyen();
                            } else if (adyenScript) {
                                adyenScript.onload = initializeAdyen;
                            }
                        }
                    },
                    {
                        name: 'Stripe',
                        description: 'Fast, furious, and occasionally legal.',
                        apiRoute: '/api/v1/payments',
                        async checkout(data) {
                            // Prepare container and clean up previous Stripe widget
                            let container = document.getElementById('checkout-container');
                            if (container) container.innerHTML = '';
                            let prevStripeForm = document.getElementById('stripe-card-element');
                            if (prevStripeForm) prevStripeForm.remove();
                            // Check if Stripe.js is already loaded or loading
                            const stripeJsSrc = 'https://js.stripe.com/v3/';
                            let stripeReady = !!window.Stripe;
                            let scriptTag = document.querySelector('script[src="' + stripeJsSrc + '"]');
                            // Only inject Stripe.js if not present
                            if (!stripeReady && !scriptTag) {
                                scriptTag = document.createElement('script');
                                scriptTag.id = 'stripe-js-script';
                                scriptTag.src = stripeJsSrc;
                                document.head.appendChild(scriptTag);
                            }
                            // Function to render the Stripe card form
                            const renderStripeForm = async () => {
                                if (!window.Stripe) {
                                    container.innerHTML = '<div class="text-red-500">Stripe.js failed to load.</div>';
                                    if (scriptTag && scriptTag.parentNode) scriptTag.parentNode.removeChild(scriptTag);
                                    return;
                                }
                                const stripe = window.Stripe(data.metadata?.clientKey || '');
                                if (!stripe) {
                                    container.innerHTML = '<div class="text-red-500">Stripe publishable key missing or invalid.</div>';
                                    if (scriptTag && scriptTag.parentNode) scriptTag.parentNode.removeChild(scriptTag);
                                    return;
                                }
                                const elements = stripe.elements({
                                    fonts: [
                                        {
                                            cssSrc: 'https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap',
                                        },
                                    ],
                                });
                                const stripeDiv = document.createElement('div');
                                stripeDiv.id = 'stripe-card-element';
                                stripeDiv.style.background = '#161616';
                                stripeDiv.style.border = '2.5px solid #39FF14';
                                stripeDiv.style.borderRadius = '1rem';
                                stripeDiv.style.padding = '2rem 1.2rem 1.5rem 1.2rem';
                                stripeDiv.style.margin = '0 auto';
                                stripeDiv.style.maxWidth = '420px';
                                stripeDiv.style.boxShadow = '0 0 16px 2px #39FF1480, 0 2px 24px #000a';
                                stripeDiv.style.fontFamily = "'Roboto', 'Fira Mono', 'Menlo', monospace";
                                container.appendChild(stripeDiv);
                                // Remove unsupported 'opacity' property from card style config
                                const card = elements.create('card', {
                                    style: {
                                        base: {
                                            color: '#39FF14',
                                            fontFamily: "'Roboto', 'Fira Mono', 'Menlo', monospace",
                                            fontSmoothing: 'antialiased',
                                            fontSize: '18px',
                                            '::placeholder': {
                                                color: '#6cffb0'
                                            },
                                            backgroundColor: '#161616',
                                            iconColor: '#39FF14',
                                        },
                                        invalid: {
                                            color: '#ff3860',
                                            iconColor: '#ff3860',
                                        },
                                    },
                                });
                                card.mount('#stripe-card-element');
                                const label = document.createElement('label');
                                label.textContent = 'Card Details';
                                label.setAttribute('for', 'stripe-card-element');
                                label.style.color = '#39FF14';
                                label.style.fontWeight = 'bold';
                                label.style.fontFamily = "'Roboto', monospace";
                                label.style.fontSize = '1.1rem';
                                label.style.marginBottom = '0.7rem';
                                label.style.display = 'block';
                                label.style.letterSpacing = '0.08em';
                                stripeDiv.insertBefore(label, stripeDiv.firstChild);
                                const payBtn = document.createElement('button');
                                payBtn.textContent = 'Pay with Stripe';
                                payBtn.className = 'mt-4 bg-yellow-400 hover:bg-yellow-300 text-black font-bold py-2 px-6 rounded-full shadow-lg uppercase tracking-wide transition duration-200';
                                payBtn.type = 'button';
                                payBtn.style.marginTop = '1.7rem';
                                payBtn.style.display = 'block';
                                payBtn.style.width = '100%';
                                stripeDiv.appendChild(payBtn);
                                // Add Stripe error message display
                                let errorDiv = document.createElement('div');
                                errorDiv.id = 'stripe-error-message';
                                errorDiv.style.color = '#ff3860';
                                errorDiv.style.marginTop = '1rem';
                                errorDiv.style.fontWeight = 'bold';
                                errorDiv.style.fontFamily = "'Roboto', monospace";
                                errorDiv.style.fontSize = '1rem';
                                stripeDiv.appendChild(errorDiv);

                                // Inline validation errors as user types
                                card.on('change', function(event) {
                                    if (event.error) {
                                        errorDiv.textContent = event.error.message;
                                    } else {
                                        errorDiv.textContent = '';
                                    }
                                });

                                payBtn.onclick = async () => {
                                    payBtn.disabled = true;
                                    payBtn.textContent = 'Be ready...!';
                                    errorDiv.textContent = '';
                                    const clientSecret = data.metadata.clientSecret;
                                    if (!clientSecret) {
                                        errorDiv.textContent = 'Missing payment intent secret.';
                                        payBtn.disabled = false;
                                        payBtn.textContent = 'Pay with Stripe';
                                        return;
                                    }
                                    const result = await stripe.confirmCardPayment(clientSecret, {
                                        payment_method: {
                                            card: card,
                                        }
                                    });
                                    if (result.error) {
                                        // Show error message in the widget, do not clear form or remove script
                                        errorDiv.textContent = 'Payment failed: ' + result.error.message;
                                        payBtn.disabled = false;
                                        payBtn.textContent = 'Pay with Stripe';
                                    } else if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                                        // Save context purchase before clearing state
                                        Alpine.store('paymentDemo').addPurchasedPayable(Alpine.store('paymentDemo').selectedPayable);
                                        Alpine.store('paymentDemo').showWidgetModal = false;
                                        Alpine.store('paymentDemo').widgetInitialized = false;
                                        Alpine.store('paymentDemo').success = '';
                                        Alpine.store('paymentDemo').error = '';
                                        Alpine.store('paymentDemo').initiatePaymentResponse = null;
                                        if (stripeDiv) stripeDiv.remove();
                                        if (container) container.innerHTML = '';
                                        if (scriptTag && scriptTag.parentNode) scriptTag.parentNode.removeChild(scriptTag);
                                        window.location.hash = 'success';
                                    } else {
                                        // Show generic error in the widget, do not clear form or remove script
                                        errorDiv.textContent = 'Payment was not successful.';
                                        payBtn.disabled = false;
                                        payBtn.textContent = 'Pay with Stripe';
                                    }
                                };
                                // Clean up on modal close
                                const cleanup = () => {
                                    if (scriptTag && scriptTag.parentNode) scriptTag.parentNode.removeChild(scriptTag);
                                    if (stripeDiv) stripeDiv.remove();
                                    if (container) container.innerHTML = '';
                                };
                                const modal = document.getElementById('payment-widget-modal');
                                if (modal) {
                                    const observer = new MutationObserver(() => {
                                        if (!Alpine.store('paymentDemo').showWidgetModal) {
                                            cleanup();
                                            observer.disconnect();
                                        }
                                    });
                                    observer.observe(modal, {attributes: true, attributeFilter: ['style', 'class']});
                                }
                            };
                            // If Stripe is ready, render immediately; else wait for script load
                            if (window.Stripe) {
                                renderStripeForm();
                            } else if (scriptTag) {
                                scriptTag.onload = renderStripeForm;
                            }
                        }
                    },
                    {
                        name: 'Nets',
                        description: 'The Scandinavian choice: colder, safer, and full of herring.',
                        apiRoute: '/api/v1/payments',
                        async checkout(data) {
                            // Clear container
                            let container = document.getElementById('checkout-container');
                            if (container) container.innerHTML = '';

                            // Always create and append a new script
                            const paymentId = data.payment?.external_id || '';
                            const script = document.createElement('script');
                            script.src = 'https://test.checkout.dibspayment.eu/v1/checkout.js?v=1';
                            script.onload = () => {
                                if (window.Dibs && typeof window.Dibs.Checkout === 'function') {
                                    const checkout = new window.Dibs.Checkout({
                                        checkoutKey: data.metadata.clientKey,
                                        paymentId: data.payment.external_id,
                                        containerId: 'checkout-container',
                                    });
                                    checkout.on('payment-completed', function (event) {
                                        // Save context purchase before clearing state
                                        Alpine.store('paymentDemo').addPurchasedPayable(Alpine.store('paymentDemo').selectedPayable);
                                        // Close the modal
                                        Alpine.store('paymentDemo').showWidgetModal = false;
                                        // Reset payment state for a clean modal next time
                                        Alpine.store('paymentDemo').widgetInitialized = false;
                                        Alpine.store('paymentDemo').success = '';
                                        Alpine.store('paymentDemo').error = '';
                                        Alpine.store('paymentDemo').initiatePaymentResponse = null;
                                        // Clean up widget DOM
                                        script.remove();
                                        container.innerHTML = '';
                                        // Redirect internally
                                        window.location.hash = 'success';
                                    })
                                    checkout.on('error', console.error);
                                    checkout.on('close', () => script.remove());
                                } else {
                                    alert('Nets payment widget failed to load. Please try again.');
                                }
                            };
                            document.body.appendChild(script);
                        }
                    },

                ],
                selectedLocation: '',
                selectedType: '',
                shuffleBlink: false,
                showWidgetModal: false,
                widgetInitialized: false,
                loading: false,
                error: '',
                success: '',
                initiatePaymentResponse: null,
                get hasPayable() { return this.selectedPayable !== null && this.selectedPayable !== undefined },
                initPayment() {
                    console.log('[initPayment] called');
                    this.loading = true;
                    this.error = '';
                    this.success = '';
                    const providerRoute = this.selectedProvider && this.providers.find(p => p.name === this.selectedProvider)?.apiRoute;
                    const payload = {
                        provider: this.selectedProvider ? this.selectedProvider.toLowerCase() : '',
                        currency: this.selectedPayable.currency,
                        amount_minor: this.selectedPayable.amount_minor,
                        payable_id: this.selectedPayable.id,
                        payable_type: 'fake_payable',
                        capture_at: this.selectedPayable.capture_at,
                        auto_capture: this.selectedPayable.auto_capture,
                        context_id: Alpine.store('context').id
                    };
                    console.log('[initPayment] Provider:', this.selectedProvider, 'Route:', providerRoute, 'Payload:', payload);
                    fetch(
                        providerRoute,
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Api-Key': Alpine.store('context').apiKey,
                            },
                            body: JSON.stringify(payload)
                        }
                    )
                    .then(res => {
                        console.log('[initPayment] API response status:', res.status);
                        if (!res.ok) {
                            return res.json().then(data => {
                                console.log('[initPayment] API error response:', data);
                                throw new Error(data.message || 'Payment failed to initialize.');
                            });
                        }
                        return res.json();
                    })
                    .then(data => {
                        console.log('[initPayment] API response data:', data);
                        this.success = 'Payment initialized successfully!';
                        this.initiatePaymentResponse = data;
                        const providerObj = this.providers.find(p => p.name === this.selectedProvider);
                        if (providerObj && typeof providerObj.checkout === 'function') {
                            console.log('[initPayment] Calling provider.checkout');
                            providerObj.checkout(data);
                        } else {
                            console.log('[initPayment] No valid checkout function for provider:', this.selectedProvider);
                        }
                    })
                    .catch(e => {
                        console.log('[initPayment] Exception:', e);
                        this.error = e.message;
                    })
                    .finally(() => {
                        this.loading = false;
                        console.log('[initPayment] Payment initialization finished');
                    });
                },
                get uniqueLocations() {
                    return [...new Set(Alpine.store('payables').map(p => p.location))];
                },
                get uniqueTypes() {
                    return [...new Set(Alpine.store('payables').map(p => p.type))];
                },
                get filteredPayables() {
                    return Alpine.store('payables').filter(p =>
                        (this.selectedLocation === '' || p.location === this.selectedLocation) &&
                        (this.selectedType === '' || p.type === this.selectedType)
                    );
                },
                shufflePayables() {
                    console.log('[shufflePayables] called');
                    this.shuffleBlink = true;
                    setTimeout(() => {
                        let list = Alpine.store('payables');
                        console.log('[shufflePayables] Before shuffle:', list.map(p => p.id));
                        for (let i = list.length - 1; i > 0; i--) {
                            const j = Math.floor(Math.random() * (i + 1));
                            [list[i], list[j]] = [list[j], list[i]];
                        }
                        console.log('[shufflePayables] After shuffle:', list.map(p => p.id));
                        Alpine.store('payables', [...list]);
                        this.shuffleBlink = false;
                        console.log('[shufflePayables] Shuffling complete');
                    }, 250);
                },
            });
            setInterval(() => { Alpine.store('paymentDemo').shufflePayables(); }, 30000);
            // Load context on Alpine init
            Alpine.store('paymentDemo').loadContext();
        });
    </script>
</head>
<body x-data x-init="
    $store.paymentDemo.randomTheme = $store.paymentDemo.themes[Math.floor(Math.random() * $store.paymentDemo.themes.length)];
    window.addEventListener('hashchange', () => {
        $store.paymentDemo.currentRoute = window.location.hash.replace('#','') || 'main';
    });
" class="animated-bg min-h-screen py-12 relative overflow-x-hidden">

    <div class="pointer-events-none fixed inset-0 z-0 opacity-40 animate-pulse-slow">
      <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none">
        <defs>
          <pattern id="smallGrid" width="10" height="10" patternUnits="userSpaceOnUse">
            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="#39FF14" stroke-width="0.5"/>
          </pattern>
          <pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse">
            <rect width="100" height="100" fill="url(#smallGrid)" />
            <path d="M 100 0 L 0 0 0 100" fill="none" stroke="#39FF14" stroke-width="1"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#grid)" />
      </svg>
    </div>

    <!-- GTA HUD-style top bar -->
    <div class="fixed top-0 left-0 w-full flex items-center justify-between px-8 py-4 bg-black/90 border-b-4 border-lime-400 z-50 shadow-2xl">
        <div class="flex items-center space-x-4">
            <a
                href="#main"
                title="Back to Main"
                style="cursor:pointer;"
                @click="window.location.hash = 'main';"
            >
                <svg viewBox="0 0 260 260" width="56" height="56" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block align-middle mr-4">
                  <rect width="260" height="260" rx="40" fill="#191919"/>
                  <ellipse cx="130" cy="185" rx="65" ry="48" fill="#222"/>
                  <ellipse cx="130" cy="180" rx="35" ry="25" fill="#fff"/>
                  <rect x="120" y="180" width="20" height="40" rx="7" fill="#39FF14" stroke="#111" stroke-width="2"/>
                  <ellipse cx="130" cy="110" rx="55" ry="60" fill="#f9d8b4" stroke="#222" stroke-width="3"/>
                  <ellipse cx="130" cy="128" rx="18" ry="10" fill="#ebb886" />
                  <ellipse cx="75" cy="110" rx="8" ry="16" fill="#f9d8b4"/>
                  <ellipse cx="185" cy="110" rx="8" ry="16" fill="#f9d8b4"/>
                  <path d="M112 142 Q130 155 148 142" stroke="#b96f38" stroke-width="4" fill="none" stroke-linecap="round"/>
                  <ellipse cx="110" cy="120" rx="6" ry="4" fill="#333"/>
                  <ellipse cx="150" cy="120" rx="6" ry="4" fill="#333"/>
                  <rect x="104" y="110" width="13" height="3" rx="1.5" fill="#a66820"/>
                  <rect x="143" y="110" width="13" height="3" rx="1.5" fill="#a66820"/>
                  <ellipse cx="110" cy="120" rx="9" ry="7" fill="#111" opacity="0.75"/>
                  <ellipse cx="150" cy="120" rx="9" ry="7" fill="#111" opacity="0.75"/>
                  <rect x="119" y="120" width="22" height="2" rx="1" fill="#111" />
                  <rect x="60" y="170" width="24" height="48" rx="8" fill="#39FF14" stroke="#222" stroke-width="2"/>
                  <rect x="62" y="182" width="20" height="9" rx="3" fill="#fff" opacity="0.3"/>
                  <rect x="176" y="170" width="24" height="48" rx="8" fill="#39FF14" stroke="#222" stroke-width="2"/>
                  <rect x="178" y="182" width="20" height="9" rx="3" fill="#fff" opacity="0.3"/>
                  <ellipse cx="72" cy="210" rx="5" ry="9" fill="#f9d8b4" />
                  <ellipse cx="188" cy="210" rx="5" ry="9" fill="#f9d8b4" />
                </svg>
            </a>
            <svg viewBox="0 0 340 56" width="240" height="56" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-14 mr-2 drop-shadow-lg select-none pointer-events-none">
              <rect width="340" height="56" rx="10" fill="#111"/>
              <rect x="3" y="3" width="334" height="50" rx="8" fill="#222" stroke="#39FF14" stroke-width="3"/>
              <text x="170" y="26" text-anchor="middle" fill="#39FF14" font-size="26" font-family="monospace" font-weight="bold" letter-spacing="4">BILL BERRY'S</text>
              <text x="170" y="48" text-anchor="middle" fill="#fff" font-size="19" font-family="monospace" font-weight="bold" letter-spacing="2">BERRY-LEGAL EMPORIUM</text>
            </svg>
            <span class="text-lime-400 text-2xl font-mono tracking-widest font-extrabold select-none">PAYMENT DEMO</span>
        </div>
        <div class="flex items-center space-x-2">
            <button
                class="flex items-center bg-gradient-to-br from-lime-400 via-green-600 to-black text-black font-extrabold px-4 py-2 rounded shadow-inner text-lg tracking-widest hover:from-yellow-300 hover:to-red-400 transition uppercase"
                @click="window.location.hash = 'purchases';"
                type="button"
                title="View Purchases"
            >
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24" class="mr-2 text-lime-400" style="min-width: 24px;">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                    <text x="12" y="17" text-anchor="middle" fill="currentColor" font-size="16" font-family="monospace" font-weight="bold">$</text>
                </svg>
                <span>Purchases</span>
            </button>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 pt-28">
    <template x-if="$store.paymentDemo.currentRoute === 'main'">
        <div>
            <h1 class="text-4xl font-extrabold text-lime-400 text-center mb-2 tracking-widest drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] font-mono uppercase">
                Bill Berry's Emporium of <span x-text="$store.paymentDemo.randomTheme"></span>
            </h1>
            <!-- Filter Bar -->
            <div class="mb-8 flex flex-wrap gap-4 items-center justify-center z-20 relative">
                <div>
                    <label class="text-lime-400 font-semibold mr-2">Location:</label>
                    <select x-model="$store.paymentDemo.selectedLocation" class="rounded bg-black/80 text-white border border-lime-400 px-2 py-1">
                        <option value="">All</option>
                        <template x-for="loc in $store.paymentDemo.uniqueLocations" :key="loc">
                            <option x-text="loc" :value="loc"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="text-lime-400 font-semibold mr-2">Type:</label>
                    <select x-model="$store.paymentDemo.selectedType" class="rounded bg-black/80 text-white border border-lime-400 px-2 py-1">
                        <option value="">All</option>
                        <template x-for="type in $store.paymentDemo.uniqueTypes" :key="type">
                            <option x-text="type" :value="type"></option>
                        </template>
                    </select>
                </div>
            </div>
            <!-- Card Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 transition-opacity duration-500"
                 :class="{'opacity-0': $store.paymentDemo.shuffleBlink, 'opacity-100': !$store.paymentDemo.shuffleBlink}"
                 x-data>
                <template x-for="item in $store.paymentDemo.filteredPayables" :key="item.id">
                    <div
                      x-transition:enter="transition ease-out duration-300"
                      x-transition:enter-start="opacity-0 scale-90"
                      x-transition:enter-end="opacity-100 scale-100"
                      x-transition:leave="transition ease-in duration-200"
                      x-transition:leave-start="opacity-100 scale-100"
                      x-transition:leave-end="opacity-0 scale-90"
                      class="bg-black/90 border-4 border-lime-400 rounded-xl shadow-xl flex flex-col items-center p-6 relative overflow-hidden hover:scale-105 transition duration-200"
                    >
                        <h2 class="text-2xl font-bold text-white mb-2 tracking-wide font-mono uppercase" x-text="item.name"></h2>
                        <p class="text-lime-300 mb-1 font-bold uppercase" x-text="item.type"></p>
                        <p class="text-gray-300 mb-4 italic" x-text="item.description"></p>
                        <div class="flex flex-col space-y-1 w-full text-sm text-gray-400 mb-4 font-mono">
                            <span><span class="font-semibold text-white">Location:</span> <span x-text="item.location"></span></span>
                            <span><span class="font-semibold text-white">Feature:</span> <span x-text="item.feature ? item.feature : '—'"></span></span>
                            <span>
                                <span class="font-semibold text-white">Price:</span>
                                <span class="text-lime-300 font-bold" x-text="(item.amount_minor / 100).toLocaleString('en-US', {style: 'currency', currency: item.currency})"></span>
                            </span>
                        </div>
                        <button
                            @click="
                                $store.paymentDemo.selectedPayable = item;
                                $store.paymentDemo.showSelectPaymentProviderModal = true;
                                $store.paymentDemo.selectedProvider = '';
                            "
                            class="mt-auto w-full bg-gradient-to-br from-lime-500 to-green-700 hover:from-red-600 hover:to-yellow-400 text-black font-extrabold text-lg py-2 rounded shadow uppercase tracking-widest transition duration-200 ease-in-out outline-none border-b-4 border-black"
                        >
                            Purchase Now
                        </button>
                        <span class="absolute top-2 right-3 bg-gradient-to-r from-black via-gray-800 to-black px-3 py-1 text-xs font-mono text-lime-300 rounded-full shadow">#<span x-text="item.id.slice(0, 6)"></span></span>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <template x-if="$store.paymentDemo.currentRoute === 'success'">
        <div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 24 24" class="mb-6 text-lime-400 mx-auto"><circle cx="12" cy="12" r="10" fill="#222"/><path d="M7 13l3 3 7-7" stroke="#39FF14" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <h2 class="text-4xl font-extrabold text-lime-400 font-mono mb-3 uppercase">Payment Successful!</h2>
            <p class="text-lg text-gray-300 mb-6">Thank you for your purchase. Have a nice day!</p>
            <a href="#main" class="mt-3 inline-block px-8 py-2 bg-lime-400 hover:bg-lime-600 text-black font-bold rounded-full font-mono uppercase shadow-lg transition">Back to Store</a>
        </div>
    </template>

        <template x-if="$store.paymentDemo.currentRoute === 'purchases'">
            <div
                class="flex flex-col items-center justify-center min-h-[60vh] text-center"
                x-init="
                    // On enter: refresh statuses and start interval
                    $store.paymentDemo.refreshRefundStatuses();
                    if ($store.paymentDemo.refundStatusRefreshInterval) clearInterval($store.paymentDemo.refundStatusRefreshInterval);
                    $store.paymentDemo.refundStatusRefreshInterval = setInterval(() => {
                        $store.paymentDemo.refreshRefundStatuses();
                    }, 30000);
                "
                @keydown.escape.window="
                    if ($store.paymentDemo.refundStatusRefreshInterval) {
                        clearInterval($store.paymentDemo.refundStatusRefreshInterval);
                        $store.paymentDemo.refundStatusRefreshInterval = null;
                    }
                "

            >
                <h2 class="text-4xl font-extrabold text-lime-400 font-mono mb-3 uppercase">Your Purchases</h2>
                <p class="text-yellow-300 font-mono text-lg mb-4">
                    Context ID: <span x-text="$store.paymentDemo.context.id"></span>
                </p>
                <p class="text-gray-400 italic font-mono text-base mb-6">
                    We don’t offer free storage, but your browser seems generous.
                </p>
                <!-- Refresh status button, shown only if at least one purchased payable has refund_status 'PROCESSING' -->
                <template x-if="($store.paymentDemo.context.purchasedPayables || []).some(p => p.refund_status === 'PROCESSING')">
                  <div class="mb-4 flex items-center justify-center">
                    <button
                        class="flex items-center bg-gradient-to-br from-lime-400 via-green-600 to-black text-black font-extrabold px-4 py-2 rounded shadow-inner text-lg tracking-widest hover:from-yellow-300 hover:to-red-400 transition uppercase mr-2"
                        @click="$store.paymentDemo.refreshRefundStatuses()"
                        :disabled="$store.paymentDemo.refundStatusesRefreshing"
                        :class="{'opacity-60 pointer-events-none': $store.paymentDemo.refundStatusesRefreshing}"
                    >
                        <template x-if="$store.paymentDemo.refundStatusesRefreshing">
                            <svg class="animate-spin h-5 w-5 mr-2 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                        </template>
                        <span x-text="$store.paymentDemo.refundStatusesRefreshing ? 'Refreshing...' : 'Refresh status'"></span>
                    </button>
                    <span class="text-xs text-gray-400 font-mono" x-show="$store.paymentDemo.refundStatusesRefreshing">Checking latest refund status...</span>
                  </div>
                </template>
                <template x-if="$store.paymentDemo.context.purchasedPayables && $store.paymentDemo.context.purchasedPayables.length > 0">
                    <div class="w-full max-w-2xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <template x-for="item in $store.paymentDemo.context.purchasedPayables" :key="item.id">
                            <div class="bg-black/90 border-4 border-lime-400 rounded-xl shadow-lg flex flex-col items-center p-4 relative">
                                <!-- Refunded badge -->
                                <template x-if="item.refunded">
                                  <span class="absolute top-2 left-3 bg-yellow-400 text-black font-bold px-3 py-1 text-xs font-mono rounded-full shadow uppercase z-10"
                                        x-text="item.refund_status === 'PROCESSING' ? 'Refund Processing' : 'Refunded'">
                                  </span>
                                </template>
                                <h3 class="text-xl font-bold text-white mb-2 tracking-wide font-mono uppercase" x-text="item.name"></h3>
                                <p class="text-lime-300 mb-1 font-bold uppercase" x-text="item.type"></p>
                                <p class="text-gray-300 mb-2 italic" x-text="item.description"></p>
                                <div class="flex flex-col space-y-1 w-full text-sm text-gray-400 mb-2 font-mono">
                                    <span><span class="font-semibold text-white">Location:</span> <span x-text="item.location"></span></span>
                                    <span><span class="font-semibold text-white">Feature:</span> <span x-text="item.feature ? item.feature : '—'"></span></span>
                                    <span>
                                    <span class="font-semibold text-white">Price:</span>
                                    <span class="text-lime-300 font-bold" x-text="(item.amount_minor / 100).toLocaleString('en-US', {style: 'currency', currency: item.currency})"></span>
                                </span>
                                </div>
                                <span class="absolute top-2 right-3 bg-gradient-to-r from-black via-gray-800 to-black px-3 py-1 text-xs font-mono text-lime-300 rounded-full shadow">#<span x-text="item.id.slice(0, 6)"></span></span>
                                <button
                                    class="mt-2 w-full bg-gradient-to-br from-yellow-400 to-red-600 hover:from-red-500 hover:to-yellow-300 text-black font-extrabold text-md py-2 rounded shadow uppercase tracking-widest transition duration-200 ease-in-out border-b-4 border-black"
                                    @click="$store.paymentDemo.payableToSell = item; $store.paymentDemo.showSellModal = true;"
                                    :disabled="item.refunded === true"
                                    :class="{'opacity-50 pointer-events-none': item.refunded === true}"
                                >
                                    <span x-text="`Sell with ${item.provider ? item.provider.charAt(0).toUpperCase() + item.provider.slice(1) : ''}`"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="!$store.paymentDemo.context.purchasedPayables || $store.paymentDemo.context.purchasedPayables.length === 0">
                    <div class="text-gray-300 mt-12 text-xl font-mono italic">You haven't purchased anything yet.</div>
                </template>
                <a href="#main"
                   class="mt-8 inline-block px-8 py-2 bg-lime-400 hover:bg-lime-600 text-black font-bold rounded-full font-mono uppercase shadow-lg transition"
                   @click="$store.paymentDemo.currentRoute = 'main';"
                >
                    Back to Store
                </a>
            </div>
        </template>

    <!-- Modal Backdrop -->
    <div
        x-show="$store.paymentDemo.showSelectPaymentProviderModal"
        class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50"
        x-transition
    >
        <!-- Modal Box -->
        <div class="bg-gray-900 border-4 border-lime-400 rounded-xl shadow-2xl w-full max-w-md mx-auto p-8 relative"
             @click.away="$store.paymentDemo.showSelectPaymentProviderModal = false"
             @keydown.escape.window="$store.paymentDemo.showSelectPaymentProviderModal = false"
        >
            <button class="absolute top-2 right-3 text-lime-400 hover:text-red-500 text-xl font-mono" @click="$store.paymentDemo.showSelectPaymentProviderModal = false">&times;</button>
            <h2 class="text-2xl font-bold text-lime-400 mb-2 font-mono uppercase">FINALIZE YOUR PAYMENT</h2>
            <template x-if="$store.paymentDemo.selectedPayable">
                <div class="mb-4">
                    <div class="text-lg text-white font-bold" x-text="$store.paymentDemo.selectedPayable.name"></div>
                    <div class="text-sm text-gray-400 italic mb-2" x-text="$store.paymentDemo.selectedPayable.description"></div>
                    <div class="font-mono text-lime-300 text-xl" x-text="($store.paymentDemo.selectedPayable.amount_minor / 100).toLocaleString('en-US', {style: 'currency', currency: $store.paymentDemo.selectedPayable.currency})"></div>
                </div>
            </template>
            <div class="mb-4">
                <label class="text-lime-400 font-semibold block mb-2 text-xl font-mono uppercase">CHOOSE PAYMENT PROVIDER</label>
                <label class="text-lime-400 font-semibold block mb-2">Provider:</label>
                <div class="flex flex-col gap-3">
                    <template x-for="prov in $store.paymentDemo.providers" :key="prov.name">
                        <label class="cursor-pointer flex items-center gap-2 p-2 rounded hover:bg-gray-800 transition">
                            <input type="radio" class="accent-lime-400" :value="prov.name" x-model="$store.paymentDemo.selectedProvider">
                            <span class="text-white font-mono font-bold" x-text="prov.name"></span>
                            <span class="text-xs text-gray-400 italic" x-text="prov.description"></span>
                        </label>
                    </template>
                </div>
            </div>
            <button
                class="mt-4 w-full bg-gradient-to-br from-lime-500 to-green-700 hover:from-red-600 hover:to-yellow-400 text-black font-extrabold text-lg py-2 rounded shadow uppercase tracking-widest transition duration-200 ease-in-out border-b-4 border-black"
                :disabled="!$store.paymentDemo.selectedProvider"
                :class="{'opacity-50 pointer-events-none': !$store.paymentDemo.selectedProvider}"
                @click="
                    $store.paymentDemo.showSelectPaymentProviderModal = false;
                    setTimeout(() => {
                        $store.paymentDemo.showWidgetModal = true;
                    }, 300);
                "
            >
                Confirm & Pay
            </button>
        </div>
    </div>

    <!-- Widget Modal -->
    <div
        x-show="$store.paymentDemo.showWidgetModal"
        class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50"
        x-transition
    >
        <div
            id="payment-widget-modal"
            class="bg-gray-900 border-4 border-yellow-400 rounded-xl shadow-2xl w-full max-w-xl mx-auto p-8 relative"
            @click.away="$store.paymentDemo.showWidgetModal = false"
            @keydown.escape.window="$store.paymentDemo.showWidgetModal = false"
        >
            <button class="absolute top-2 right-3 text-yellow-400 hover:text-red-500 text-xl font-mono" @click="$store.paymentDemo.showWidgetModal = false">&times;</button>
            <h2 class="text-2xl font-bold text-yellow-400 mb-4 font-mono uppercase">Payment Widget</h2>
            <p class="text-white font-mono mb-6 italic">
              Processing payment via <span x-text="$store.paymentDemo.selectedProvider"></span>... Failure to pay may result in very sore kneecaps, revoked SaaS licenses, or unexplained disappearances. Have a nice day.
            </p>
            <div class="bg-black/70 text-lime-300 border border-yellow-400 rounded p-6 text-center font-mono">
                <template x-if="$store.paymentDemo.hasPayable">
                  <div>
                    <form x-show="!$store.paymentDemo.widgetInitialized" class="space-y-4 text-left" @submit.prevent="
                        $store.paymentDemo.widgetInitialized = true;
                        $nextTick(() => { $store.paymentDemo.initPayment(); });
                    ">
                      <div>
                          <label class="block mb-1 text-white">Amount (in cents):</label>
                          <input type="number" min="1" class="w-full px-3 py-2 bg-black text-lime-300 border border-yellow-400 rounded" x-model.number="$store.paymentDemo.selectedPayable.amount_minor">
                      </div>
                      <div>
                          <label class="block mb-1 text-white">Capture At:</label>
                          <input type="datetime-local" class="w-full px-3 py-2 bg-black text-lime-300 border border-yellow-400 rounded" x-model="$store.paymentDemo.selectedPayable.capture_at">
                      </div>
                      <div class="flex items-center space-x-2">
                          <input type="checkbox" id="auto_capture" class="accent-yellow-400" x-model="$store.paymentDemo.selectedPayable.auto_capture">
                          <label for="auto_capture" class="text-white">Auto Capture</label>
                      </div>
                      <p class="text-xs text-gray-400 italic mt-2">This form lets you customize payment parameters before submitting to the selected provider.</p>
                      <div class="pt-4 text-center">
                          <button
                              type="submit"
                              class="bg-yellow-400 hover:bg-yellow-300 text-black font-bold py-2 px-6 rounded-full shadow-lg uppercase tracking-wide transition duration-200"
                          >
                              Pay Now
                          </button>
                      </div>
                    </form>
                    <div x-show="$store.paymentDemo.widgetInitialized" class="text-center text-yellow-300 font-mono text-lg p-6">
                        <div x-show="$store.paymentDemo.loading">
                            <p>Initializing payment widget... Please wait.</p>
                        </div>
                        <div x-show="$store.paymentDemo.error" x-transition>
                            <p class="text-red-400" x-text="$store.paymentDemo.error"></p>
                        </div>
                        <div x-show="$store.paymentDemo.success" x-transition>
                            <p class="text-green-400" x-text="$store.paymentDemo.success"></p>
                            <div id="checkout-container"></div>
                        </div>
                    </div>
                  </div>
                </template>
            </div>
        </div>
    </div>

    <!-- GTA-style HUD Money Notification -->
    <div class="fixed bottom-6 right-8 flex items-center space-x-2 z-50 animate-pulse">
        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" class="text-lime-400">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
            <text x="12" y="17" text-anchor="middle" fill="currentColor" font-size="16" font-family="monospace" font-weight="bold">$</text>
        </svg>
        <span class="text-lime-300 text-lg font-extrabold font-mono drop-shadow">
            <span x-text="($store.paymentDemo.currentBalance / 100).toLocaleString('en-US', {style: 'currency', currency: $store.paymentDemo.context.currency})"></span>
        </span>
    </div>

    <!-- Reset Demo Button (bottom left, HUD style) -->
    <div class="fixed bottom-6 left-8 flex items-center space-x-2 z-50">
        <button
            class="flex items-center bg-gradient-to-br from-lime-400 via-green-600 to-black text-black font-extrabold px-4 py-2 rounded shadow-inner text-lg tracking-widest hover:from-yellow-300 hover:to-red-400 transition uppercase"
            @click="$store.paymentDemo.resetDemo()"
            type="button"
            title="Reset Demo"
        >
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" class="mr-2 text-lime-400" style="min-width: 24px;">
                <path d="M12 5V2L7 7l5 5V8c3.31 0 6 2.69 6 6a6 6 0 1 1-6-6z" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Reset Demo</span>
        </button>
    </div>

</div>

</div>

    <!-- Welcome Modal -->
    <div
        x-show="$store.paymentDemo.showWelcomeModal"
        class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50"
        x-transition
    >
        <div class="bg-gray-900 border-4 border-lime-400 rounded-2xl shadow-2xl max-w-lg w-full p-8 text-center relative">
            <button class="absolute top-2 right-3 text-lime-400 hover:text-red-400 text-2xl font-mono"
                    @click="$store.paymentDemo.showWelcomeModal = false">&times;</button>
            <h2 class="text-3xl font-extrabold text-lime-400 font-mono mb-3 uppercase">Welcome to Bill Berry's Berry Legal Emporium</h2>
            <p class="text-lg text-gray-200 mb-6 font-mono">
                Around here, deals move fast, questions are few, and paperwork is... best ignored. Storage? That's your browser's problem, not ours.<br><br>
                Consider this your lucky day: we've "deposited" <span class="text-yellow-300 font-bold">$1,000,000.00 USD</span> straight into your account. Don't ask where it came from.<br><br>
                Buy what you want—but remember, every purchase leaves a trail. If you ever regret your choices, you might be able to sell your payables back... at a ludicrous premium.<br><br>
                Welcome to Bill Berry's. Play smart. Play fast. Play... legally. Or at least look like you are.
            </p>
            <button
                class="mt-3 px-8 py-2 bg-lime-400 hover:bg-lime-600 text-black font-bold rounded-full font-mono uppercase shadow-lg transition"
                @click="$store.paymentDemo.showWelcomeModal = false"
            >
                Got it!
            </button>
        </div>
    </div>

    <!-- Sell Modal -->
    <div
        x-show="$store.paymentDemo.showSellModal"
        class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50"
        x-transition
    >
        <div class="bg-gray-900 border-4 border-yellow-400 rounded-2xl shadow-2xl max-w-md w-full p-8 text-center relative">
            <button class="absolute top-2 right-3 text-yellow-400 hover:text-red-400 text-2xl font-mono"
                    @click="$store.paymentDemo.showSellModal = false; $store.paymentDemo.payableToSell = null;">&times;</button>
            <h2 class="text-2xl font-extrabold text-yellow-400 font-mono mb-4 uppercase">Sell Payable</h2>
            <template x-if="$store.paymentDemo.payableToSell">
                <div>
                    <div class="text-lg text-white font-bold mb-2" x-text="$store.paymentDemo.payableToSell.name"></div>
                    <div class="text-sm text-gray-400 italic mb-2" x-text="$store.paymentDemo.payableToSell.description"></div>
                    <div class="font-mono text-yellow-300 text-xl mb-4"
                         x-text="($store.paymentDemo.payableToSell.amount_minor / 100).toLocaleString('en-US', {style: 'currency', currency: $store.paymentDemo.payableToSell.currency})">
                    </div>
                </div>
            </template>
            <p class="text-gray-300 mb-6">Are you sure you want to sell this payable? The amount will be refunded to your account balance.</p>
            <button
                class="mt-3 px-8 py-2 bg-yellow-400 hover:bg-lime-600 text-black font-bold rounded-full font-mono uppercase shadow-lg transition flex items-center justify-center"
                @click="$store.paymentDemo.sellPayable()"
                :disabled="$store.paymentDemo.sellingRefundInFlight"
                :class="{'opacity-60 pointer-events-none': $store.paymentDemo.sellingRefundInFlight}"
            >
                <template x-if="$store.paymentDemo.sellingRefundInFlight">
                    <svg class="animate-spin h-5 w-5 mr-2 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </template>
                <span x-text="$store.paymentDemo.sellingRefundInFlight ? 'Processing...' : 'Confirm Sale'"></span>
            </button>
        </div>
    </div>
</body>
</html>
