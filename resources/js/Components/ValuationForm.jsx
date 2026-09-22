import { useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import ValuationProcessingState from '@/Components/LandingPages/ValuationProcessingState';
import Icon from './ui/Icon';
import { Button } from './ui/Button';

const propertyFields = [
    ['street', 'Straße', 'text', 'z. B. Gartenstraße', 'address-line1'],
    ['house_number', 'Hausnummer', 'text', 'z. B. 12', 'address-line2'],
    ['zip', 'Postleitzahl', 'text', 'z. B. 40721', 'postal-code'],
    ['city', 'Ort', 'text', 'z. B. Hilden', 'address-level2'],
    ['living_area', 'Wohnfläche in m²', 'number', 'z. B. 120'],
    ['plot_area', 'Grundstück in m²', 'number', 'z. B. 450'],
    ['construction_year', 'Baujahr', 'number', 'z. B. 1995'],
    ['rooms', 'Zimmer', 'number', 'z. B. 4'],
];

function Field({ id, label, error, ...props }) {
    return <div className="form-field"><label htmlFor={id}>{label}</label><input id={id} className="input" aria-invalid={Boolean(error)} aria-describedby={error ? id + '-error' : undefined} {...props} />{error && <p id={id + '-error'} className="field-error" role="alert">{error}</p>}</div>;
}

export function ValuationForm({ action, onSuccess, propertyTypes = [] }) {
    const [step, setStep] = useState(0);
    const heading = useRef(null);
    const form = useRef(null);
    const { data, setData, post, processing, errors } = useForm({
        first_name: '', last_name: '', email: '', phone: '', notes: '',
        phone_contact_consent: false, valuation_disclaimer_accepted: false,
        property: { property_type: '', street: '', house_number: '', zip: '', city: '', country: 'DE', construction_year: '', living_area: '', plot_area: '', rooms: '' },
    });
    const changeProperty = (key, value) => setData('property', { ...data.property, [key]: value });
    const changeStep = (value) => {
        setStep(value);
        requestAnimationFrame(() => { heading.current?.focus(); heading.current?.scrollIntoView({ block: 'nearest' }); });
    };
    const submit = (event) => {
        event.preventDefault();
        if (step === 0) { if (form.current.reportValidity()) changeStep(1); return; }
        post(action, {
            preserveScroll: 'errors',
            onSuccess,
            onError: (validation) => {
                changeStep(Object.keys(validation).some((key) => key.startsWith('property.')) ? 0 : 1);
            },
        });
    };

    return (
        <div className="valuation-form">
            <div className="form-progress" aria-label="Fortschritt"><span className={step === 0 ? 'active' : 'complete'} aria-current={step === 0 ? 'step' : undefined}><b>{step > 0 ? '✓' : '1'}</b> Ihre Immobilie</span><i /><span className={step === 1 ? 'active' : ''} aria-current={step === 1 ? 'step' : undefined}><b>2</b> Ihr Kontakt</span></div>
            <div className="form-heading"><p className="eyebrow">Schritt {step + 1} von 2</p><h2 ref={heading} tabIndex={-1}>{step === 0 ? 'Um welche Immobilie geht es?' : 'Wohin dürfen wir den Bericht senden?'}</h2><p>{step === 0 ? 'Ein paar Angaben helfen uns, Ihre Immobilie einzuordnen.' : 'Sie erhalten Ihre Ersteinschätzung als PDF per E-Mail.'}</p></div>
            <form ref={form} onSubmit={submit} noValidate={false}>
                {step === 0 ? (
                    <>
                        <fieldset className="property-selector"><legend>Immobilienart</legend><div className="property-options">{propertyTypes.map(({ value, label, icon }) => (
                            <label className={data.property.property_type === value ? 'property-option selected' : 'property-option'} key={value}>
                                <input type="radio" name="property_type" value={value} checked={data.property.property_type === value} onChange={() => changeProperty('property_type', value)} />
                                <Icon name={icon} size={25} /><span>{label}</span>
                            </label>
                        ))}</div>{errors['property.property_type'] && <p className="field-error" role="alert">{errors['property.property_type']}</p>}</fieldset>
                        <div className="form-section-title"><Icon name="pin" size={18} /> Adresse & Eckdaten</div>
                        <div className="field-grid">{propertyFields.map(([key, label, type, placeholder, autoComplete]) => (
                            <Field key={key} id={key} label={label} type={type} placeholder={placeholder} autoComplete={autoComplete} inputMode={key === 'zip' ? 'numeric' : undefined}
                                min={type === 'number' ? (key === 'construction_year' ? 1700 : 1) : undefined}
                                max={key === 'construction_year' ? new Date().getFullYear() : undefined}
                                step={key === 'rooms' ? '0.5' : undefined}
                                value={data.property[key]} onChange={(event) => changeProperty(key, event.target.value)} error={errors['property.' + key]} />
                        ))}</div>
                    </>
                ) : (
                    <>
                        <div className="contact-context"><Icon name="home" /><span>{propertyTypes.find((type) => type.value === data.property.property_type)?.label || 'Ihre Immobilie'}{data.property.city ? ' in ' + data.property.city : ''}</span><button type="button" onClick={() => changeStep(0)}>Ändern</button></div>
                        <div className="field-grid">
                            <Field id="first_name" label="Vorname" autoComplete="given-name" value={data.first_name} onChange={(event) => setData('first_name', event.target.value)} error={errors.first_name} />
                            <Field id="last_name" label="Nachname" autoComplete="family-name" value={data.last_name} onChange={(event) => setData('last_name', event.target.value)} error={errors.last_name} />
                            <Field id="email" label="E-Mail *" type="email" autoComplete="email" required value={data.email} onChange={(event) => setData('email', event.target.value)} error={errors.email} />
                            <Field id="phone" label="Telefon *" type="tel" autoComplete="tel" required value={data.phone} onChange={(event) => setData('phone', event.target.value)} error={errors.phone} />
                        </div>
                        <div className="form-field notes-field"><label htmlFor="notes">Was sollten wir noch wissen? <span>optional</span></label><textarea id="notes" rows={3} className="input" placeholder="Besonderheiten Ihrer Immobilie oder Fragen an uns …" value={data.notes} onChange={(event) => setData('notes', event.target.value)} aria-invalid={Boolean(errors.notes)} />{errors.notes && <p className="field-error" role="alert">{errors.notes}</p>}</div>
                        <div className="consent-group">
                            {[
                                ['phone_contact_consent', 'Ich bin damit einverstanden, dass 2 COME HOME Immobilien mich telefonisch kontaktiert.'],
                                ['valuation_disclaimer_accepted', 'Ich habe verstanden, dass es sich um eine unverbindliche Ersteinschätzung handelt, daraus kein Anspruch auf einen bestimmten Verkaufspreis entsteht und ich die Widerrufsbelehrung sowie die Datenschutzerklärung zur Kenntnis genommen habe.'],
                            ].map(([key, text]) => <div key={key}><label className="consent-row"><input type="checkbox" required checked={data[key]} onChange={(event) => setData(key, event.target.checked)} aria-invalid={Boolean(errors[key])} /><span>{text}</span></label>{errors[key] && <p className="field-error" role="alert">{errors[key]}</p>}</div>)}
                        </div>
                        <ValuationProcessingState isProcessing={processing} />
                    </>
                )}
                <div className="form-actions">{step === 1 && <button type="button" className="back-button" disabled={processing} onClick={() => changeStep(0)}>Zurück</button>}<Button type="submit" isLoading={processing} className="continue-button">{step === 0 ? 'Weiter zu Ihren Kontaktdaten' : 'Ersteinschätzung anfordern'}<Icon name="arrow" size={18} /></Button></div>
                <p className="form-assurance"><Icon name="lock" size={14} /> Ihre Angaben werden vertraulich behandelt.{step === 1 && ' * Pflichtfelder'}</p>
            </form>
        </div>
    );
}
