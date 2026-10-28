export function numberValue(value) {
    const text = String(value ?? '').trim().replace(',', '.');
    if (text === '') throw new Error('Renseignez tous les champs nécessaires.');
    const result = Number(text);
    if (!Number.isFinite(result)) throw new Error('Renseignez des nombres valides.');
    return result;
}

function positive(value, maximum, label) {
    const result = numberValue(value);
    if (result <= 0 || result > maximum) throw new Error(`${label} doit être supérieur à 0 et inférieur ou égal à ${maximum}.`);
    return result;
}

export function calculateVolume({ shape, length, width, diameter, depth }) {
    const waterDepth = positive(depth, 10, 'La profondeur');
    let area;
    if (shape === 'round') {
        area = Math.PI * (positive(diameter, 100, 'Le diamètre') / 2) ** 2;
    } else if (shape === 'rectangle' || shape === 'oval') {
        area = positive(length, 100, 'La longueur') * positive(width, 100, 'La largeur');
        if (shape === 'oval') area *= Math.PI / 4;
    } else {
        throw new Error('Choisissez une forme de bassin proposée.');
    }
    const cubicMetres = area * waterDepth;
    return { cubicMetres, litres: Math.round(cubicMetres * 1000) };
}

export function calculateBudget(cubicMetres, waterPrice, monthlyBudget, months) {
    const volume = positive(cubicMetres, 100000, 'Le volume');
    const price = numberValue(waterPrice);
    const monthly = numberValue(monthlyBudget);
    const season = numberValue(months);
    if (price < 0 || price > 1000 || monthly < 0 || monthly > 10000 || !Number.isInteger(season) || season < 1 || season > 12) {
        throw new Error('Vérifiez les budgets positifs et le nombre de mois (1 à 12).');
    }
    return { filling: Math.round(volume * price * 100) / 100, care: Math.round(monthly * season * 100) / 100 };
}

function comparison(value, minimum, maximum) {
    return value < minimum ? 'low' : value > maximum ? 'high' : 'within';
}

export function analyseWater({ ph, chlorine, alkalinity, poolType, stabilizer, alkalinityMin = '', alkalinityMax = '' }) {
    const p = numberValue(ph);
    const c = numberValue(chlorine);
    const a = numberValue(alkalinity);
    if (p < 0 || p > 14 || c < 0 || c > 20 || a < 0 || a > 500) throw new Error('Vérifiez les limites des trois mesures.');
    if (!['pool', 'spa'].includes(poolType) || !['no', 'yes', 'unknown'].includes(stabilizer)) throw new Error('Précisez le bassin et le stabilisant.');
    const minimum = poolType === 'spa' ? 3 : stabilizer === 'yes' ? 2 : 1;
    // CDC: pool/spa minimums depend on the disinfectant and cyanuric acid.
    // No automatic dosage or assertion that swimming is safe is produced.
    let chlorineStatus = c < minimum ? 'low' : 'review';
    if (poolType === 'pool' && stabilizer !== 'unknown') chlorineStatus = comparison(c, minimum, 4);
    if (stabilizer === 'unknown' || (poolType === 'spa' && stabilizer === 'yes') || c > 10) chlorineStatus = 'review';
    let alkalinityStatus = 'reference';
    let range = null;
    if (String(alkalinityMin).trim() !== '' || String(alkalinityMax).trim() !== '') {
        const min = numberValue(alkalinityMin);
        const max = numberValue(alkalinityMax);
        if (min < 0 || max > 500 || min >= max) throw new Error('La plage TAC doit comporter un minimum inférieur au maximum (0 à 500).');
        alkalinityStatus = comparison(a, min, max);
        range = [min, max];
    }
    return {
        ph: { value: p, status: comparison(p, 7, 7.8), range: [7, 7.8] },
        chlorine: { value: c, status: chlorineStatus, minimum },
        alkalinity: { value: a, status: alkalinityStatus, range },
    };
}
