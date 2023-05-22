var VALORCARDS = [{
    name: 'Banescard',
    bins: /^(603182)[0-9]{10,12}/,
    codeLength: 3
  }, {
    name: 'Maxxvan',
    bins: /^(603182)[0-9]{10,12}/,
    codeLength: 3
  }, {
    name: 'Cabal',
    bins: /^(604324|604330|604337|604203|604338)[0-9]{10,12}/,
    codeLength: 3
  }, {
    name: 'GoodCard',
    bins: /^(606387|605680|605674|603574)[0-9]{10,12}/,
    codeLength: 3
  }, {
    name: 'Elo',
    bins: /^(401178|401179|431274|438935|451416|457393|457631|457632|504175|627780|636297|636368|(506699|5067[0-6]\d|50677[0-8])|(50900\d|5090[1-9]\d|509[1-9]\d{2})|65003[1-3]|(65003[5-9]|65004\d|65005[0-1])|(65040[5-9]|6504[1-3]\d)|(65048[5-9]|65049\d|6505[0-2]\d|65053[0-8])|(65054[1-9]|6505[5-8]\d|65059[0-8])|(65070\d|65071[0-8])|65072[0-7]|(6509[0-9])|(65165[2-9]|6516[6-7]\d)|(65500\d|65501\d)|(65502[1-9]|6550[3-4]\d|65505[0-8]))[0-9]{10,12}/,
    codeLength: 3
  }, {
    name: 'Diners',
    bins: /^3(?:0[0-5]|[68][0-9])[0-9]{11}$/,
    codeLength: 3
  }, {
    name: 'Discover',
    bins: /^6(?:011|5[0-9]{2}|4[4-9][0-9]{1}|(22(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[01][0-9]|92[0-5]$)[0-9]{10}$))[0-9]{12}$/,
    codeLength: 4
  }, {
    name: 'Amex',
    bins: /^3[47][0-9]{13}$/,
    codeLength: 4
  }, {
    name: 'Aura',
    bins: /^50[0-9]{14,17}$/,
    codeLength: 3
  }, {
    name: 'Mastercard',
    bins: /^(603136|603689|608619|606200|603326|605919|608783|607998|603690|604891|603600|603134|608718|603680|608710|604998)|(5[1-5][0-9]{14}|2221[0-9]{12}|222[2-9][0-9]{12}|22[3-9][0-9]{13}|2[3-6][0-9]{14}|27[01][0-9]{13}|2720[0-9]{12})$/,
    codeLength: 3
  }, {
    name: 'Visa',
    bins: /^4[0-9]{12}(?:[0-9]{3})?$/,
    codeLength: 3
  }, {
    name: 'Hipercard',
    bins: /^(38[0-9]{17}|60[0-9]{14})$/,
    codeLength: 3
  }, {
    name: 'JCB',
    bins: /^(?:2131|1800|35\d{3})\d{11}$/,
    codeLength: 3
  }];
  
  var VALORMILLENNIUM = 1000;
  var VALOR_DEFAULT_CODE_LENGTH = 3;
  
  ValorPayCC = function() {
  };

  ValorPayCC.prototype.getCreditCardNameByNumber = function(number) {
    return this.findCreditCardObjectByNumber(number).name || 'Credit card is invalid!';
  };

  ValorPayCC.prototype.isSecurityCodeValid = function(creditCardNumber, securityCode) {
    var numberLength = this.getCreditCardCodeLengthByNumber(creditCardNumber);
    return new RegExp("^[0-9]{".concat(numberLength, "}$")).test(securityCode);
  };

  ValorPayCC.prototype.isExpirationDateValid = function(month, year) {
    return this.isValidMonth(month) && this.isValidYear(year) && this.isFutureOrPresentDate(month, year);
  };

  ValorPayCC.prototype.isValid = function(number) {
    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var cards = options.cards;
    var rawNumber = this.removeNonNumbersCaracteres(number);
    if (this.hasSomeInvalidDigit(number) || !this.hasCorrectLength(rawNumber)) {
      return false;
    }
    var sum = this.sumNumber(rawNumber);
    return this.checkSum(sum) && this.validateCardsWhenRequired(number, cards);
  };

  ValorPayCC.prototype.validateCardsWhenRequired = function(number, cards) {
    return !cards || !cards.length || this.validateCards(number, cards);
  };
   
  ValorPayCC.prototype.validateCards = function(number, cards) {
    return this.areCardsSupported(cards) && cards.map(function (c) {
      return c.toLowerCase();
    }).includes(this.getCreditCardNameByNumber(number).toLowerCase());
  };

  ValorPayCC.prototype.hasCorrectLength = function(number) {
    return number && number.length <= 19;
  };

  ValorPayCC.prototype.removeNonNumbersCaracteres = function(number) {
    return number.replace(/\D/g, '');
  };

  ValorPayCC.prototype.hasSomeInvalidDigit = function(creditcardNumber) {
    var invalidDigits = new RegExp('[^0-9- ]');
    return invalidDigits.test(creditcardNumber);
  };

  ValorPayCC.prototype.checkSum = function(sum) {
    return sum > 0 && sum % 10 === 0;
  };

  ValorPayCC.prototype.areCardsSupported = function(passedCards) {
    var supportedCards = VALORCARDS.map(function (c) {
      return c.name.toLowerCase();
    });
    return passedCards.every(function (c) {
      return supportedCards.includes(c.toLowerCase());
    });
  };

  ValorPayCC.prototype.findCreditCardObjectByNumber = function(number) {
    if (!number) return {};
    var numberOnly = number.replace(/[^\d]/g, '');
    return VALORCARDS.find(function (card) {
      return card.bins.test(numberOnly) && card;
    }) || {};
  };

  ValorPayCC.prototype.getCreditCardCodeLengthByNumber = function(number) {
    return this.findCreditCardObjectByNumber(number).codeLength || VALOR_DEFAULT_CODE_LENGTH;
  };

  ValorPayCC.prototype.isValidMonth = function(month) {
    return !isNaN(month) && month >= 1 && month <= 12;
  };

  ValorPayCC.prototype.isValidYear = function(year) {
    return !isNaN(year) && this.isValidFullYear(this.formatFullYear(year));
  };

  ValorPayCC.prototype.formatFullYear = function(year) {
    if (year.length === 2) return this.dateRange(year);
    return year.length === 4 ? year : 0;
  };

  ValorPayCC.prototype.dateRange = function() {
    var increaseYear = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
    var year = parseInt(increaseYear);
    var today = new Date();
    return Math.floor(today.getFullYear() / VALORMILLENNIUM) * VALORMILLENNIUM + year;
  };

  ValorPayCC.prototype.isValidFullYear = function(year) {
    return year >= this.dateRange() && year <= this.dateRange(VALORMILLENNIUM);
  };

  ValorPayCC.prototype.isFutureOrPresentDate = function(month, year) {
    var fullYear = this.formatFullYear(year);
    var currentDate = new Date();
    var expirationDate = new Date();
    currentDate.setFullYear(currentDate.getFullYear(), currentDate.getMonth(), 1);
    expirationDate.setFullYear(fullYear, month - 1, 1);
    return currentDate <= expirationDate;
  };

  ValorPayCC.prototype.sumNumber = function(number) {
    var computed = [0, 2, 4, 6, 8, 1, 3, 5, 7, 9];
    var sum = 0;
    var digit = 0;
    var i = number.length;
    var even = true;
    while (i--) {
      digit = Number(number[i]);
      sum += (even = !even) ? computed[digit] : digit;
    }
    return sum;
  };