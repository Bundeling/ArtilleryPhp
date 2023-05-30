# Contributing to ArtilleryPhp

First off, thank you for considering contributing to this project! It's people like you that make open source such a thrilling place. Whether you're contributing code, reporting bugs, or giving feedback on the API, your efforts are deeply appreciated.

## Library goal

The aim with this project is to reduce the complexity of writing and maintaining Artillery scripts.

* **Maintainability:** The first script we're testing for production has seen a 40% reduction in lines of code with reusable components.
* **Complexity:** Reduce overhead and streamline development with the help of intellisense and comprehensive PHPDoc comments.
* **Platform integration:** Natively bind values from your target PHP platform.

## How Can I Contribute?

We're always open to contributions that help this project improve. Here are a few areas where you can help:

* **Code and Features:** Implement missing features or fix bugs. Feedback on the API is also welcome.
* **Engines:** Priority on a feature-complete and well-tested WebSocket. If you're interested in Socket.io or another request type, we'd love to hear your ideas.
* **Documentation:** Keep the documentation and descriptions up-to-date with [Artillery.io docs](https://www.artillery.io/docs) whenever possible. Aim to make examples as realistic and demonstrative as possible.
* **Planning:** Contribute to a possible roadmap for the library past feature-completion.
* **Abstraction and `/doc` generation:** For now, we have decided not to have `Flow` and `Config` classes, to reduce clutter in the generated documentation. If you're up for a task to create a better **phpDocumentor template**, or even a different generator implementation for the PHPDoc tags used, here are some guidelines for the abstraction:
  1. Create classes where `Artillery extends Config`, and `Scenario extends Flow`, and move the existing methods from their regions into these classes. We extend them to keep the reduction in complexity.
  2. Update or add methods to bring together the abstraction (e.g., `setConfig(Config ..)`, `Artillery::fromConfig(Config ...`, and `addEnvironment(Config ...)`, etc.).
* **Deprecations and versions** Currently I have decided to aim for V2, but this is another area where we'd love to hear your ideas.

Currently, the project contains no form of validation, as it would balloon the complexity of the library. If you're interested in contributing to this, please open an issue to discuss the best approach.

We value all the people who contribute in various ways to our project. It wouldn't be the same without you.
Remember, any contribution counts. Thank you for your interest, and we're excited to see what you'll bring to our project!
