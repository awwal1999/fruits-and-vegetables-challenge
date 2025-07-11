# 🍎🥕 Fruits and Vegetables

## 🎯 Goal
We want to build a service which will take a `request.json` and:
* Process the file and create two separate collections for `Fruits` and `Vegetables`
* Each collection has methods like `add()`, `remove()`, `list()`;
* Units have to be stored as grams;
* Store the collections in CSV files (fruits.csv and vegetables.csv)
* Provide an API endpoint to query the collections. As a bonus, this endpoint can accept filters to be applied to the returning collection.
* Provide another API endpoint to add new items to the collections (i.e., your storage engine).
* As a bonus you might:
  * consider giving an option to decide which units are returned (kilograms/grams);
  * how to implement `search()` method collections;
  * use latest version of Symfony's to embed your logic

### ✔️ Codebase setup
You can run this project **with or without Docker**.

---

### 🔧 Option 1: Without Docker

If you already have PHP and Composer installed:

```bash
composer install
symfony server:start
```

To setup with docker
```bash
docker-compose build --no-cache
docker-compose up -d
```

1. **Run the tests** (recommended):
   ```bash
   bin/phpunit
   ```

2. **Process the request.json file**:
   ```bash
   bin/console app:process-request
   ```

3. **Use the API endpoints**:
   - POST `/api/process` - Process a JSON payload
   - GET `/api/items` - Get items with optional filters
   - POST `/api/items` - Add a new item

4. **Check CSV files**:
   After processing, check `/storage/item.csv`

## 💡 Hints before you start working on it
* Keep KISS, DRY, YAGNI, SOLID principles in mind
* We value a clean domain model, without unnecessary code duplication or complexity
* Think about how you will handle input validation
* Follow generally-accepted good practices, such as no logic in controllers, information hiding (see the first hint).
* Timebox your work - we expect that you would spend between 3 and 4 hours.
* Your code should be tested
* We don't care how you handle data persistence, no bonus points for having a complex method