pipeline {
    agent any

    environment {
        RECIPIENTS = 'ghadaderouiche8@gmail.com'
        DOCKER_USERNAME = 'ghada522'
        DOCKER_PASSWORD = 'Ghoughou*2001'
    }

    stages {
        stage('Cloner le code') {
            steps {
                git url: 'https://github.com/ghada634/testprojet.git'
            }
        }

        stage('Exécuter les tests') {
            steps {
                bat '.\\vendor\\bin\\phpunit tests'
            }
        }

        stage('Analyse SonarQube') {
            steps {
                script {
                    try {
                        withSonarQubeEnv('SonarQubeServer') {
                            bat 'sonar-scanner -Dsonar.projectKey=testprojet -Dsonar.sources=. -Dsonar.php.tests.reportPath=tests'
                        }
                    } catch (Exception e) {
                        echo "Erreur lors de l'analyse SonarQube : ${e.getMessage()}"
                        currentBuild.result = 'FAILURE'
                        throw e
                    }
                }
            }
        }

        stage('Construire et Lancer Docker Compose') {
            steps {
                script {
                    try {
                        // Ensure Docker Compose is available and the Docker Compose file is present
                        bat 'docker-compose -f docker-compose.yml up -d --build'
                    } catch (Exception e) {
                        echo "Erreur lors du lancement de Docker Compose : ${e.getMessage()}"
                        currentBuild.result = 'FAILURE'
                        throw e
                    }
                }
            }
        }

        stage('Scan Trivy pour vulnérabilités Docker') {
            steps {
                script {
                    try {
                        bat 'trivy image edoc-app'
                    } catch (Exception e) {
                        echo "Erreur lors du scan Trivy : ${e.getMessage()}"
                        currentBuild.result = 'FAILURE'
                        throw e
                    }
                }
            }
        }

        stage('Pusher l\'image Docker vers Docker Hub') {
            steps {
                script {
                    try {
                        bat "docker login -u ${DOCKER_USERNAME} -p ${DOCKER_PASSWORD}"
                        bat "docker tag edoc-app ${DOCKER_USERNAME}/edoc-app:latest"
                        bat "docker push ${DOCKER_USERNAME}/edoc-app:latest"
                    } catch (Exception e) {
                        echo "Erreur lors du push de l'image Docker : ${e.getMessage()}"
                        currentBuild.result = 'FAILURE'
                        throw e
                    }
                }
            }
        }

        stage('Deploy to AWS') {
            steps {
                withCredentials([sshUserPrivateKey(credentialsId: 'ghada-key', keyFileVariable: 'SSH_KEY_FILE')]) {
                    script {
                        // Fix file permissions
                        bat 'icacls %SSH_KEY_FILE% /inheritance:r'
                        bat 'icacls %SSH_KEY_FILE% /grant:r "test:F"'

                        // Now SSH
                        bat 'ssh -i %SSH_KEY_FILE% -o StrictHostKeyChecking=no ubuntu@44.211.128.195 "docker pull ghada522/edoc-app:latest && docker stop app || true && docker rm app || true && docker run -d --name app -p 80:80 ghada522/edoc-app:latest"'
                    }
                }
            }
        }
    }

    post {
        success {
            mail(
                to: RECIPIENTS,
                subject: "✅ SUCCESS - ${env.JOB_NAME} #${env.BUILD_NUMBER}",
                body: "Bonjour Ghada,\n\nLe build a réussi. Consulte les détails ici : ${env.BUILD_URL}",
                mimeType: 'text/plain',
                charset: 'UTF-8'
            )
        }

        failure {
            mail(
                to: RECIPIENTS,
                subject: "❌ ECHEC - ${env.JOB_NAME} #${env.BUILD_NUMBER}",
                body: "Bonjour Ghada 👩‍💻,\n\nLe build a échoué 💥 !\n\nVérifie les logs ici : ${env.BUILD_URL}",
                mimeType: 'text/plain',
                charset: 'UTF-8'
            )
        }
    }
}
