export const invalidInputs = {
  xss: `"><script>alert(1)</script>`,
  sql: `' OR 1=1 --`,
  oversizedCode: 'x'.repeat(300),
  invalidCode: 'Invalid Code !@#',
  empty: ''
};

export const uploadFiles = {
  validImage: '../e2e-pw/assets/floral.jpg',
  validCsv: '../e2e-pw/assets/attributes.csv',
  validXlsx: '../e2e-pw/assets/1k_products.xlsx',
  emptyXlsx: '../e2e-pw/assets/empty.xlsx'
};
