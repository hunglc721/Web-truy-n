const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const jsSource = readFileSync(path.join(__dirname, '../../laravel-blade/public/js/admin-bulk-chapter-upload.js'), 'utf8');

// Extract helper functions and parser from admin-bulk-chapter-upload.js
const context = {};
vm.runInNewContext(`
  ${jsSource.match(/function normalizeChapterNumber[\s\S]*?^  \}/m)[0]}
  ${jsSource.match(/function compareChapterNumbers[\s\S]*?^  \}/m)[0]}
  ${jsSource.match(/function cleanFolderTitle[\s\S]*?^  \}/m)[0]}
  ${jsSource.match(/function parseChapterFolderName[\s\S]*?^  \}/m)[0]}
  globalThis.normalizeChapterNumber = normalizeChapterNumber;
  globalThis.compareChapterNumbers = compareChapterNumbers;
  globalThis.cleanFolderTitle = cleanFolderTitle;
  globalThis.parseChapterFolderName = parseChapterFolderName;
`, context);

const { normalizeChapterNumber, compareChapterNumbers, parseChapterFolderName } = context;

test('parses required chapter folders: Ch.90, Ch.90.1, Ch.90.01, Ch.90.001, Ch.90.12345, Ch.91', () => {
  const cases = [
    { input: 'Ch.90', expectedNum: '90', expectedTitle: 'Chapter 90' },
    { input: 'Ch.90.1', expectedNum: '90.1', expectedTitle: 'Chapter 90.1' },
    { input: 'Ch.90.01', expectedNum: '90.01', expectedTitle: 'Chapter 90.01' },
    { input: 'Ch.90.001', expectedNum: '90.001', expectedTitle: 'Chapter 90.001' },
    { input: 'Ch.90.12345', expectedNum: '90.12345', expectedTitle: 'Chapter 90.12345' },
    { input: 'Ch.91', expectedNum: '91', expectedTitle: 'Chapter 91' },
  ];

  for (const { input, expectedNum, expectedTitle } of cases) {
    const result = parseChapterFolderName(input);
    assert.equal(result.chapterNumber, expectedNum, `Failed chapterNumber for ${input}`);
    assert.equal(result.title, expectedTitle, `Failed title for ${input}`);
  }
});

test('parses various chapter folders without bleeding decimals into title', () => {
  const cases = [
    { input: 'Ch.187', expectedNum: '187', expectedTitle: 'Chapter 187' },
    { input: 'Ch.187.5', expectedNum: '187.5', expectedTitle: 'Chapter 187.5' },
    { input: 'Ch.187.25', expectedNum: '187.25', expectedTitle: 'Chapter 187.25' },
    { input: 'Chapter 187.5', expectedNum: '187.5', expectedTitle: 'Chapter 187.5' },
    { input: 'Vol.16 Ch.187.5 - Bonus Chapter', expectedNum: '187.5', expectedTitle: 'Bonus Chapter' },
    { input: 'Vol.16 Ch.0140 - Opening the Decisive Battle (en)', expectedNum: '140', expectedTitle: 'Opening the Decisive Battle' },
    { input: '0.5', expectedNum: '0.5', expectedTitle: 'Chapter 0.5' },
    { input: 'Ch.0.5', expectedNum: '0.5', expectedTitle: 'Chapter 0.5' },
    { input: '00187.5', expectedNum: '187.5', expectedTitle: 'Chapter 187.5' },
    { input: 'Ch.00187.5', expectedNum: '187.5', expectedTitle: 'Chapter 187.5' },
    { input: 'Ch.999.999999', expectedNum: '999.999999', expectedTitle: 'Chapter 999.999999' },
  ];

  for (const { input, expectedNum, expectedTitle } of cases) {
    const result = parseChapterFolderName(input);
    assert.equal(result.chapterNumber, expectedNum, `Failed chapterNumber for ${input}`);
    assert.equal(result.title, expectedTitle, `Failed title for ${input}`);
  }
});

test('normalization rules: 90.1 == 90.10 == 90.100, strips leading zeros, normalizes trailing zeros', () => {
  assert.equal(normalizeChapterNumber('90.1'), '90.1');
  assert.equal(normalizeChapterNumber('90.10'), '90.1');
  assert.equal(normalizeChapterNumber('90.100'), '90.1');
  assert.equal(normalizeChapterNumber('90.1'), normalizeChapterNumber('90.10'));
  assert.equal(normalizeChapterNumber('90.10'), normalizeChapterNumber('90.100'));

  // Leading zeros in integer part stripped
  assert.equal(normalizeChapterNumber('090.1'), '90.1');
  assert.equal(normalizeChapterNumber('001.25'), '1.25');

  // Trailing zeros in decimal part normalized
  assert.equal(normalizeChapterNumber('90.100'), '90.1');
  assert.equal(normalizeChapterNumber('90.0100'), '90.01');
  assert.equal(normalizeChapterNumber('90.000'), '90');
});

test('inequality rules: 90.01 != 90.1', () => {
  assert.notEqual(normalizeChapterNumber('90.01'), normalizeChapterNumber('90.1'));
  assert.equal(normalizeChapterNumber('90.01'), '90.01');
  assert.equal(normalizeChapterNumber('90.1'), '90.1');
});

test('invalid chapter formats are rejected', () => {
  const invalidCases = [
    '90.',
    '.5',
    '90..1',
    '90.1.2',
    'abc',
    '90a.1',
    '-90.1',
  ];

  for (const inv of invalidCases) {
    assert.equal(normalizeChapterNumber(inv), null, `Expected ${inv} to be rejected as null`);
  }
});

test('sorts chapters mathematically: 90 < 90.001 < 90.01 < 90.1 < 90.11 < 90.5 < 91', () => {
  const naturalCollator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });
  const rawList = [
    { chapterNumber: '91', folderName: 'Ch.91' },
    { chapterNumber: '90.5', folderName: 'Ch.90.5' },
    { chapterNumber: '90.01', folderName: 'Ch.90.01' },
    { chapterNumber: '90', folderName: 'Ch.90' },
    { chapterNumber: '90.11', folderName: 'Ch.90.11' },
    { chapterNumber: '90.12345', folderName: 'Ch.90.12345' },
    { chapterNumber: '90.1', folderName: 'Ch.90.1' },
    { chapterNumber: '90.001', folderName: 'Ch.90.001' },
  ];

  rawList.sort((a, b) => {
    const comp = compareChapterNumbers(a.chapterNumber, b.chapterNumber);
    if (comp !== 0) return comp;
    return naturalCollator.compare(a.folderName, b.folderName);
  });

  const sortedNumbers = rawList.map(item => item.chapterNumber);
  assert.deepEqual(sortedNumbers, [
    '90',
    '90.001',
    '90.01',
    '90.1',
    '90.11',
    '90.12345',
    '90.5',
    '91',
  ]);
});

test('duplicate detection does not report false duplicates for 90, 90.1, 90.01, 90.001, 90.12345, 91', () => {
  const folderNames = [
    'Ch.90',
    'Ch.90.1',
    'Ch.90.01',
    'Ch.90.001',
    'Ch.90.12345',
    'Ch.91',
  ];

  const numbers = new Map();
  for (const name of folderNames) {
    const parsed = parseChapterFolderName(name);
    const norm = normalizeChapterNumber(parsed.chapterNumber);
    assert.notEqual(norm, null);
    const count = numbers.get(norm) || 0;
    numbers.set(norm, count + 1);
  }

  // Every single one should appear exactly once
  for (const [num, count] of numbers.entries()) {
    assert.equal(count, 1, `False duplicate detected for ${num}`);
  }
});

test('duplicate detection catches identical normalized chapters (90.1 == 90.10 == 90.100)', () => {
  const folderNames = [
    'Ch.90.1',
    'Ch.90.10',
    'Ch.90.100',
  ];

  const numbers = new Map();
  for (const name of folderNames) {
    const parsed = parseChapterFolderName(name);
    const norm = normalizeChapterNumber(parsed.chapterNumber);
    const count = numbers.get(norm) || 0;
    numbers.set(norm, count + 1);
  }

  assert.equal(numbers.size, 1);
  assert.equal(numbers.get('90.1'), 3);
});
