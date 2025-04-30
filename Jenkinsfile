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
                git url: 'https://github.com/ghada634/peplinesec.git'
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

        stage('Déployer sur AWS') {
            steps {
                withCredentials([sshUserPrivateKey(credentialsId: 'ghada-key', keyFileVariable: 'SSH_KEY_FILE')]) {
                    script {
                        bat 'icacls %SSH_KEY_FILE% /inheritance:r'
                        bat 'icacls %SSH_KEY_FILE% /grant:r "test:F"'

                        bat '''
                            ssh -i %SSH_KEY_FILE% -o StrictHostKeyChecking=no ubuntu@3.84.219.170 ^
                            "cd ~/peplinesec && \
                            docker-compose down && \
                            git pull && \
                            docker-compose pull && \
                            docker-compose up -d"
                        '''
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
                body: """
                    <html>
                        <body>
                            <p>Bonjour Ghada 👩‍💻,</p>
                            <p>✅ Le build a <strong>réussi</strong>.</p>
                            <p>Consulte les détails ici : <a href="${env.BUILD_URL}">${env.BUILD_URL}</a></p>
                        </body>
                    </html>
                """,
                mimeType: 'text/html',
                charset: 'UTF-8'
            )
        }

        failure {
            mail(
                to: RECIPIENTS,
                subject: "❌ ECHEC - ${env.JOB_NAME} #${env.BUILD_NUMBER}",
                body: """
                    <html>
                        <body>
                            <p>Bonjour Ghada 👩‍💻,</p>
                            <p>❌ Le build a <strong>échoué</strong> 💥 !</p>
                            <p>Vérifie les logs ici : <a href="${env.BUILD_URL}">${env.BUILD_URL}</a></p>
                        </body>
                    </html>
                """,
                mimeType: 'text/html',
                charset: 'UTF-8'
            )
        }
    }
}
