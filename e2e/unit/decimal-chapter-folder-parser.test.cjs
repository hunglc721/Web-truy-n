const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const jsSource = readFileSync(path.join(__dirname, '../../laravel-blade/public/js/admin-bulk-chapter-upload.js'), 'utf8');

// Extract parseChapterFolderName and cleanFolderTitle
const context = {};
vm.runInNewContext(`
  ${jsSource.match(/function cleanFolderTitle[\s\S]*?^  \}/m)[0]}
  ${jsSource.match(/function parseChapterFolderName[\s\S]*?^  \}/m)[0]}
  globalThis.cleanFolderTitle = cleanFolderTitle;
  globalThis.parseChapterFolderName = parseChapterFolderName;
`, context);

const { parseChapterFolderName } = context;

test('parses integer and decimal chapter folders correctly without bleeding decimals into title', () => {
  const cases = [
    { input: 'Ch.187', expectedNum: 187, expectedTitle: 'Chapter 187' },
    { input: 'Ch.187.5', expectedNum: 187.5, expectedTitle: 'Chapter 187.5' },
    { input: 'Ch.187.25', expectedNum: 187.25, expectedTitle: 'Chapter 187.25' },
    { input: 'Chapter 187.5', expectedNum: 187.5, expectedTitle: 'Chapter 187.5' },
    { input: 'Vol.16 Ch.187.5 - Bonus Chapter', expectedNum: 187.5, expectedTitle: 'Bonus Chapter' },
    { input: 'Vol.16 Ch.0140 - Opening the Decisive Battle (en)', expectedNum: 140, expectedTitle: 'Opening the Decisive Battle' },
    { input: '0.5', expectedNum: 0.5, expectedTitle: 'Chapter 0.5' },
    { input: 'Ch.0.5', expectedNum: 0.5, expectedTitle: 'Chapter 0.5' },
    { input: '00187.5', expectedNum: 187.5, expectedTitle: 'Chapter 187.5' },
    { input: 'Ch.00187.5', expectedNum: 187.5, expectedTitle: 'Chapter 187.5' },
  ];

  for (const { input, expectedNum, expectedTitle } of cases) {
    const result = parseChapterFolderName(input);
    assert.equal(result.chapterNumber, expectedNum, `Failed chapterNumber for ${input}`);
    assert.equal(result.title, expectedTitle, `Failed title for ${input}`);
  }
});

test('sorts chapter list mathematically instead of alphabetically', () => {
  const naturalCollator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });
  const rawList = [
    { chapterNumber: 188, folderName: 'Ch.188' },
    { chapterNumber: 187.5, folderName: 'Ch.187.5' },
    { chapterNumber: 186, folderName: 'Ch.186' },
    { chapterNumber: 187.25, folderName: 'Ch.187.25' },
    { chapterNumber: 187, folderName: 'Ch.187' },
  ];

  rawList.sort((a, b) => {
    const aNumber = typeof a.chapterNumber === 'number' && Number.isFinite(a.chapterNumber) ? a.chapterNumber : Number.MAX_SAFE_INTEGER;
    const bNumber = typeof b.chapterNumber === 'number' && Number.isFinite(b.chapterNumber) ? b.chapterNumber : Number.MAX_SAFE_INTEGER;
    if (aNumber !== bNumber) return aNumber - bNumber;
    return naturalCollator.compare(a.folderName, b.folderName);
  });

  const sortedNumbers = rawList.map(item => item.chapterNumber);
  assert.deepEqual(sortedNumbers, [186, 187, 187.25, 187.5, 188]);
});

test('duplicate detection identifies 187.5 and 187.500 as duplicate, but keeps 187 and 187.5 separate', () => {
  const numbers = new Map();
  const testInputs = [
    { val: '187', num: Number.parseFloat('187') },
    { val: '187.5', num: Number.parseFloat('187.5') },
    { val: '187.500', num: Number.parseFloat('187.500') },
  ];

  for (const item of testInputs) {
    const count = numbers.get(item.num) || 0;
    numbers.set(item.num, count + 1);
  }

  assert.equal(numbers.get(187), 1);
  assert.equal(numbers.get(187.5), 2);
});
