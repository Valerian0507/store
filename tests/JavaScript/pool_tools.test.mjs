import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const source = await readFile(new URL('../../assets/lib/pool_tools.js', import.meta.url), 'utf8');
const { numberValue, calculateVolume, calculateBudget, analyseWater } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

test('rectangular pool: 8 x 4 x 1.4 is 44800 litres', () => {
    const result = calculateVolume({ shape: 'rectangle', length: 8, width: 4, depth: '1,4' });
    assert.equal(result.litres, 44800);
    assert.ok(Math.abs(result.cubicMetres - 44.8) < 1e-10);
});
test('round pool uses diameter; oval pool uses the ellipse area', () => {
    assert.ok(Math.abs(calculateVolume({ shape: 'round', diameter: 4, depth: 1 }).cubicMetres - 4 * Math.PI) < 1e-10);
    assert.ok(Math.abs(calculateVolume({ shape: 'oval', length: 8, width: 4, depth: 1 }).cubicMetres - 8 * Math.PI) < 1e-10);
});
test('missing, negative, non-finite dimensions and unknown shapes are rejected', () => {
    for (const depth of ['', -1, 0, Infinity, NaN]) assert.throws(() => calculateVolume({ shape: 'rectangle', length: 8, width: 4, depth }));
    assert.throws(() => calculateVolume({ shape: 'triangle', depth: 1 }));
    assert.throws(() => numberValue(''));
});
test('budget is derived from user prices and active months only', () => {
    assert.deepEqual(calculateBudget(44.8, 4, 15, 6), { filling: 179.2, care: 90 });
    assert.deepEqual(calculateBudget(44.8, 0, 0, 12), { filling: 0, care: 0 });
    for (const months of [0, 13, 1.5]) assert.throws(() => calculateBudget(44.8, 4, 15, months));
});
const measures = { ph: 7.4, chlorine: 1.5, alkalinity: 100, poolType: 'pool', stabilizer: 'no' };
test('chlorine interpretation changes with stabilizer and spa', () => {
    assert.equal(analyseWater(measures).chlorine.status, 'within');
    assert.equal(analyseWater({ ...measures, stabilizer: 'yes' }).chlorine.status, 'low');
    assert.equal(analyseWater({ ...measures, poolType: 'spa' }).chlorine.status, 'low');
    assert.equal(analyseWater({ ...measures, stabilizer: 'unknown' }).chlorine.status, 'review');
    assert.equal(analyseWater({ ...measures, chlorine: 11 }).chlorine.status, 'review');
    assert.equal(analyseWater({ ...measures, poolType: 'spa', stabilizer: 'yes', chlorine: 3 }).chlorine.status, 'review');
});
test('pH boundaries are inclusive and TAC needs the user reference range', () => {
    assert.equal(analyseWater({ ...measures, ph: 7 }).ph.status, 'within');
    assert.equal(analyseWater({ ...measures, ph: 7.8 }).ph.status, 'within');
    assert.equal(analyseWater({ ...measures, ph: 7.9 }).ph.status, 'high');
    assert.equal(analyseWater(measures).alkalinity.status, 'reference');
    assert.equal(analyseWater({ ...measures, alkalinityMin: 80, alkalinityMax: 120 }).alkalinity.status, 'within');
    assert.throws(() => analyseWater({ ...measures, alkalinityMin: 120, alkalinityMax: 80 }));
    assert.throws(() => analyseWater({ ...measures, ph: -1 }));
    assert.throws(() => analyseWater({ ...measures, chlorine: '' }));
});
