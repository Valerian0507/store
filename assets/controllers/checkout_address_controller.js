import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const manualActions = document.getElementById('checkout-manual-actions');
        const useSavedAddressesButton = document.getElementById('checkout-use-saved-addresses');
        const changeButton = document.getElementById('checkout-change-address');
        const addAddressButton = document.getElementById('checkout-add-address');
        const addressPanel = document.getElementById('checkout-addresses-panel');
        const manualFieldsWrapper = document.getElementById('checkout-manual-address-fields');
        const manualFields = document.querySelectorAll('.js-manual-address-field');
        const addressRadios = document.querySelectorAll('input[name="selectedAddress"]');
        const manualAddressRadio = document.getElementById('selectedAddressManual');

        const selectedAddressBlock = document.getElementById('checkout-selected-address');
        const currentFullname = document.getElementById('checkout-current-fullname');
        const currentAddressLine = document.getElementById('checkout-current-address-line');
        const currentDefaultBadge = document.getElementById('checkout-current-default-badge');

        if (!manualFieldsWrapper) {
            return;
        }

        function updateSelectedAddressSummary() {
            const checkedRadio = document.querySelector('input[name="selectedAddress"]:checked');

            if (!checkedRadio || checkedRadio.value === '0') {
                selectedAddressBlock?.classList.add('d-none');
                return;
            }

            const firstName = checkedRadio.dataset.firstName ?? '';
            const lastName = checkedRadio.dataset.lastName ?? '';
            const street = checkedRadio.dataset.street ?? '';
            const city = checkedRadio.dataset.city ?? '';
            const postalCode = checkedRadio.dataset.postalCode ?? '';
            const country = checkedRadio.dataset.country ?? '';
            const isDefault = checkedRadio.dataset.isDefault === '1';

            const fullName = `${firstName} ${lastName}`.trim();

            if (currentFullname) {
                currentFullname.textContent = fullName !== '' ? fullName : 'Account owner';
            }
            if (currentAddressLine) {
                currentAddressLine.textContent = `${street}, ${city}, ${postalCode}, ${country}`;
            }
            currentDefaultBadge?.classList.toggle('d-none', !isDefault);
            selectedAddressBlock?.classList.remove('d-none');
        }

        function toggleManualAddressFields() {
            const checkedRadio = document.querySelector('input[name="selectedAddress"]:checked');
            if (!checkedRadio) {
                return;
            }

            const useManualAddress = checkedRadio.value === '0';

            if (useManualAddress) {
                manualFieldsWrapper.classList.remove('d-none');
                manualFields.forEach((field) => { field.disabled = false; });
                manualActions?.classList.remove('d-none');
                selectedAddressBlock?.classList.add('d-none');
                addressPanel?.classList.add('d-none');
            } else {
                manualFieldsWrapper.classList.add('d-none');
                manualFields.forEach((field) => { field.disabled = true; });
                manualActions?.classList.add('d-none');
                updateSelectedAddressSummary();
                addressPanel?.classList.add('d-none');
            }
        }

        changeButton?.addEventListener('click', () => {
            addressPanel?.classList.toggle('d-none');
        });

        addAddressButton?.addEventListener('click', () => {
            if (manualAddressRadio) {
                manualAddressRadio.checked = true;
            }
            toggleManualAddressFields();
        });

        useSavedAddressesButton?.addEventListener('click', () => {
            addressPanel?.classList.remove('d-none');
            manualFieldsWrapper.classList.add('d-none');
            manualActions?.classList.add('d-none');
        });

        addressRadios.forEach((radio) => {
            radio.addEventListener('change', () => {
                toggleManualAddressFields();
                updateSelectedAddressSummary();
            });
        });

        toggleManualAddressFields();
        updateSelectedAddressSummary();
    }
}
