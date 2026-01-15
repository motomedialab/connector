# Contributing to MotoMediaLab Connector

We welcome contributions to the MotoMediaLab Connector! Please take a moment to review this document to make the contribution process as smooth as possible.

## Code of Conduct

Please note that this project is released with a Contributor Code of Conduct. By participating in this project you agree to abide by its terms.

## How Can I Contribute?

### Reporting Bugs

*   Ensure the bug hasn't already been reported by searching on GitHub under [Issues](https://github.com/motomedialab/connector/issues).
*   If you're unable to find an open issue addressing the problem, [open a new one](https://github.com/motomedialab/connector/issues/new). Be sure to include a clear title and description, as much relevant information as possible, and a code sample or an executable test case demonstrating the expected behavior that is not occurring.

### Suggesting Enhancements

*   Start by searching on GitHub under [Issues](https://github.com/motomedialab/connector/issues) to see if the enhancement has already been suggested.
*   If not, [open a new issue](https://github.com/motomedialab/connector/issues/new). Clearly describe the feature, including its purpose, potential use cases, and how it might fit into the existing project.

### Development Setup

To get started with development, follow these steps:

1.  **Fork the repository:** Click the "Fork" button on the top right of this repository's GitHub page.
2.  **Clone your forked repository:**
    ```bash
    git clone https://github.com/YOUR_USERNAME/connector.git
    cd connector
    ```
3.  **Install dependencies:**
    ```bash
    composer install
    ```

### Coding Style

This package adheres to the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard and uses [Laravel Pint](https://laravel.com/docs/master/pint) for code formatting. Before submitting a pull request, please ensure your code is formatted correctly by running:

```bash
./vendor/bin/pint
```

### Running Tests

To ensure your changes don't break existing functionality and new features work as expected, please run the test suite:

```bash
./vendor/bin/pest
```

Please add tests for new features and bug fixes.

### Submitting a Pull Request (PR)

1.  **Create a new branch:**
    ```bash
    git checkout -b feature/your-feature-name
    ```
    or
    ```bash
    git checkout -b bugfix/your-bugfix-name
    ```
2.  **Make your changes.**
3.  **Ensure code style and tests pass.**
4.  **Commit your changes** with a clear and descriptive commit message.
5.  **Push your branch** to your forked repository.
6.  **Open a Pull Request** against the `main` branch of the original `motomedialab/connector` repository.
    *   Provide a clear title and description for your PR.
    *   Reference any related issues.

Thank you for your contributions!
