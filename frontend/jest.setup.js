require('@testing-library/jest-dom');

// fetchのモック
global.fetch = jest.fn();

// localStorageのモック
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
};
global.localStorage = localStorageMock;

// Cookieのモック
const cookiesMock = {
  get: jest.fn(),
  set: jest.fn(),
  remove: jest.fn(),
};
global.cookies = cookiesMock; 